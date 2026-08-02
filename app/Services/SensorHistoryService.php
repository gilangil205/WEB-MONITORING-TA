<?php

namespace App\Services;

use App\Models\SensorReading;
use App\Models\Notification;
use App\Http\Controllers\SensorController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SensorHistoryService
{
    /**
     * Menyimpan data sensor periodik (terpusat) dengan proteksi Atomic Lock dan Time-Slot 15 menit.
     *
     * @param float $suhu
     * @param float $udara
     * @param float $tanah
     * @param float $nilaiFuzzy
     * @param string $keputusanSistem
     * @param string|null $inputDeteksiYolo
     * @param float|null $inputConfidenceYolo
     * @param string|null $imagePath
     * @param string $source Logging diagnostic tag (MQTT / HTTP)
     * @return array Status eksekusi dan rincian diagnostik
     */
    public static function savePeriodicIfDue(
        float $suhu,
        float $udara,
        float $tanah,
        float $nilaiFuzzy,
        string $keputusanSistem,
        ?string $inputDeteksiYolo = null,
        ?float $inputConfidenceYolo = null,
        ?string $imagePath = null,
        string $source = 'UNKNOWN'
    ): array {
        $result = [
            'status'                         => 'skipped_lock',
            'id'                             => null,
            'lock_acquired'                  => false,
            'total_sensor_records_before'    => 0,
            'last_periodic_record_id'        => null,
            'last_periodic_created_at'       => null,
            'current_time'                   => Carbon::now()->format('Y-m-d H:i:s'),
            'difference_minutes'             => null,
            'current_15_minute_slot'         => null,
            'periodic_record_exists_in_slot' => false,
            'save_decision'                  => 'LOCK_NOT_ACQUIRED',
            'insert_error'                   => null,
        ];

        // 1. Dapatkan Atomic Lock terpusat
        $lockAcquired = Cache::lock('sensor_periodic_db_save_lock', 10)->get(function () use (
            $suhu, $udara, $tanah, $nilaiFuzzy, $keputusanSistem,
            $inputDeteksiYolo, $inputConfidenceYolo, $imagePath, $source, &$result
        ) {
            $result['lock_acquired'] = true;
            $now = Carbon::now();
            $result['current_time'] = $now->format('Y-m-d H:i:s');

            // Hitung total record sebelum insert
            try {
                $result['total_sensor_records_before'] = SensorReading::count();
            } catch (\Throwable $e) {
                $result['total_sensor_records_before'] = 0;
            }

            // 2. Hitung Awal dan Akhir Slot 15 Menit saat ini (misal 23:15:00 s.d 23:29:59)
            $minuteSlot = floor($now->minute / 15) * 15;
            $slotStart  = $now->copy()->minute((int)$minuteSlot)->second(0);
            $slotEnd    = $slotStart->copy()->addMinutes(15)->subSecond();
            $result['current_15_minute_slot'] = $slotStart->format('Y-m-d H:i:s') . ' s.d ' . $slotEnd->format('H:i:s');

            // 3. Pengecekan Slot: Apakah sudah ada record sensor periodik pada slot 15m ini?
            $alreadySavedInSlot = SensorReading::where('created_at', '>=', $slotStart)
                ->where('created_at', '<=', $slotEnd)
                ->where(function ($q) {
                    $q->whereNull('deteksi_yolo')->orWhere('deteksi_yolo', 'OFF');
                })
                ->exists();

            $result['periodic_record_exists_in_slot'] = $alreadySavedInSlot;

            // 4. Record Periodik Terakhir
            $latestPeriodic = SensorReading::where(function ($q) {
                $q->whereNull('deteksi_yolo')->orWhere('deteksi_yolo', 'OFF');
            })->latest()->first();

            if ($latestPeriodic) {
                $result['last_periodic_record_id']  = $latestPeriodic->id;
                $result['last_periodic_created_at'] = $latestPeriodic->created_at ? $latestPeriodic->created_at->format('Y-m-d H:i:s') : null;
                $diffMinutes = $latestPeriodic->created_at ? $latestPeriodic->created_at->diffInMinutes($now) : 999;
                $result['difference_minutes'] = $diffMinutes;
            } else {
                $result['last_periodic_record_id']  = 'NULL';
                $result['last_periodic_created_at'] = 'NULL';
                $result['difference_minutes']        = 999;
            }

            // 5. Penentuan Keputusan Penyimpanan (Eksplisit untuk Tabel Kosong & Slot)
            $shouldSave = false;

            if (!$latestPeriodic) {
                $shouldSave = true;
                $result['save_decision'] = 'STORE_FIRST_RECORD';
            } elseif ($alreadySavedInSlot) {
                $shouldSave = false;
                $result['save_decision'] = 'SKIP_SLOT_EXISTS';
            } elseif ($result['difference_minutes'] < 15) {
                $shouldSave = false;
                $result['save_decision'] = 'SKIP_INTERVAL_LESS_THAN_15M';
            } else {
                $shouldSave = true;
                $result['save_decision'] = 'STORE_PERIODIC_RECORD';
            }

            if ($shouldSave) {
                try {
                    // Tentukan file gambar permanen jika ada
                    $permanentPath = $imagePath;
                    if (!$permanentPath && Storage::disk('public')->exists('kamera/latest_live.jpg')) {
                        $permanentFilename = 'kamera/periodic_' . time() . '_' . uniqid() . '.jpg';
                        Storage::disk('public')->copy('kamera/latest_live.jpg', $permanentFilename);
                        $permanentPath = $permanentFilename;
                    }

                    $sensor = SensorReading::create([
                        'suhu'             => $suhu,
                        'kelembapan_udara' => $udara,
                        'kelembapan_tanah' => $tanah,
                        'nilai_fuzzy'      => $nilaiFuzzy,
                        'image'            => $permanentPath,
                        'deteksi'          => $keputusanSistem,
                        'deteksi_yolo'     => $inputDeteksiYolo,
                        'confidence_yolo'  => $inputConfidenceYolo,
                    ]);

                    $result['status'] = 'stored';
                    $result['id']     = $sensor->id;

                    // Logging Diagnostik
                    Log::info(sprintf(
                        "[PERIODIC_SAVE] SOURCE=%s | ID=%d | Decision=%s | Slot=%s",
                        $source, $sensor->id, $result['save_decision'], $result['current_15_minute_slot']
                    ));

                    // Notifikasi hanya untuk status TINGGI (saat transisi !TINGGI -> TINGGI)
                    if ($keputusanSistem === 'TINGGI') {
                        $prevStatusWasTinggi = $latestPeriodic && ($latestPeriodic->deteksi === 'TINGGI');

                        if (!$prevStatusWasTinggi) {
                            $lockNotif = Cache::lock('notification_fuzzy_high_transition_lock', 5);
                            if ($lockNotif->get()) {
                                try {
                                    $controller = app(SensorController::class);
                                    $controller->createNotification('TINGGI', round($nilaiFuzzy, 4), $sensor);
                                    Log::info("[NOTIFICATION_TRIGGERED] Status TINGGI (Transisi dari " . ($latestPeriodic->deteksi ?? 'AWAL') . ")");
                                } finally {
                                    $lockNotif->release();
                                }
                            }
                        } else {
                            Log::info("[NOTIFICATION_SKIPPED] Status tetap TINGGI (TINGGI -> TINGGI)");
                        }
                    }
                } catch (\Throwable $e) {
                    $result['status']       = 'insert_error';
                    $result['insert_error'] = $e->getMessage();
                    Log::error("[PERIODIC_INSERT_ERROR] SOURCE={$source} | Error: " . $e->getMessage());
                }
            } else {
                $result['status'] = 'skipped_interval';
                Log::info(sprintf(
                    "[PERIODIC_SKIP] SOURCE=%s | Decision=%s | DiffMinutes=%s | SlotExists=%s",
                    $source, $result['save_decision'], (string)$result['difference_minutes'], $alreadySavedInSlot ? 'YES' : 'NO'
                ));
            }
        });

        return $result;
    }
}
