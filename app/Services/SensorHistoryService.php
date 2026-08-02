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
            $suhu,
            $udara,
            $tanah,
            $nilaiFuzzy,
            $keputusanSistem,
            $inputDeteksiYolo,
            $inputConfidenceYolo,
            $imagePath,
            $source,
            &$result
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
                    // ── LOGIKA PENENTUAN GAMBAR SNAPSHOT PERIODIK (15m) ──
                    // Snapshot OFF hanya boleh dibuat jika SELURUH kondisi benar:
                    // (a) camera_last_updated_at tersedia dan belum kedaluwarsa (≤2m)
                    // (b) latest_yolo_updated_at tersedia dan belum kedaluwarsa (≤2m)
                    // (c) latest_yolo_status secara eksplisit bernilai 'OFF' (tanpa default)
                    // (d) latest_live.jpg tersedia di disk
                    // (e) frame token terbaru belum pernah digunakan sebagai snapshot periodik

                    $cameraLastUpdatedAt   = Cache::get('camera_last_updated_at');
                    $latestYoloStatus      = Cache::get('latest_yolo_status');        // NULL jika tidak ada
                    $latestYoloUpdatedAt   = Cache::get('latest_yolo_updated_at');    // NULL jika tidak ada
                    $latestYoloFrameToken  = Cache::get('latest_yolo_frame_token');   // NULL jika tidak ada
                    $lastUsedFrameToken    = Cache::get('last_periodic_snapshot_frame_token');
                    $liveFileExists        = Storage::disk('public')->exists('kamera/latest_live.jpg');

                    // (a) Cek kesegaran timestamp kamera (≤ CAMERA_EXPIRATION_MINUTES)
                    $isCameraFresh = false;
                    if ($cameraLastUpdatedAt && $liveFileExists) {
                        $cameraDiffMinutes = Carbon::parse($cameraLastUpdatedAt)->diffInMinutes($now);
                        if ($cameraDiffMinutes <= SensorController::CAMERA_EXPIRATION_MINUTES) {
                            $isCameraFresh = true;
                        }
                    }

                    // (b) Cek kesegaran timestamp YOLO (≤ CAMERA_EXPIRATION_MINUTES)
                    $isYoloFresh = false;
                    if ($latestYoloUpdatedAt) {
                        $yoloDiffMinutes = Carbon::parse($latestYoloUpdatedAt)->diffInMinutes($now);
                        if ($yoloDiffMinutes <= SensorController::CAMERA_EXPIRATION_MINUTES) {
                            $isYoloFresh = true;
                        }
                    }

                    // (c) Status YOLO harus secara eksplisit 'OFF'
                    $isYoloExplicitlyOff = ($latestYoloStatus !== null && strtoupper(trim((string)$latestYoloStatus)) === 'OFF');

                    // (e) Frame token harus berbeda dari snapshot periodik sebelumnya
                    $isNewFrame = ($latestYoloFrameToken !== null && $latestYoloFrameToken !== $lastUsedFrameToken);

                    // Tentukan snapshot path
                    $permanentPath  = null;
                    $snapshotYolo   = null;

                    if ($isCameraFresh && $isYoloFresh && $isYoloExplicitlyOff && $liveFileExists && $isNewFrame) {
                        $snapshotFilename = 'kamera/snapshot_15m_' . time() . '_' . uniqid() . '.jpg';
                        Storage::disk('public')->copy('kamera/latest_live.jpg', $snapshotFilename);
                        $permanentPath = $snapshotFilename;
                        $snapshotYolo  = 'OFF';

                        // Simpan frame token yang sudah digunakan
                        Cache::forever('last_periodic_snapshot_frame_token', $latestYoloFrameToken);
                    }

                    // Konsistensi data: image & deteksi_yolo harus sinkron
                    // - image terisi → deteksi_yolo wajib terisi ('OFF')
                    // - image NULL   → deteksi_yolo mengikuti input asli atau NULL
                    $finalDeteksiYolo = $permanentPath !== null
                        ? $snapshotYolo
                        : $inputDeteksiYolo;

                    $sensor = SensorReading::create([
                        'suhu'             => $suhu,
                        'kelembapan_udara' => $udara,
                        'kelembapan_tanah' => $tanah,
                        'nilai_fuzzy'      => $nilaiFuzzy,
                        'image'            => $permanentPath,
                        'deteksi'          => $keputusanSistem,
                        'deteksi_yolo'     => $finalDeteksiYolo,
                        'confidence_yolo'  => $inputConfidenceYolo,
                    ]);

                    $result['status'] = 'stored';
                    $result['id']     = $sensor->id;

                    // Logging Diagnostik
                    Log::info(sprintf(
                        "[PERIODIC_SAVE] SOURCE=%s | ID=%d | Decision=%s | Slot=%s",
                        $source,
                        $sensor->id,
                        $result['save_decision'],
                        $result['current_15_minute_slot']
                    ));

                    // Fitur Notifikasi tidak lagi digunakan dalam alur produksi
                } catch (\Throwable $e) {
                    $result['status']       = 'insert_error';
                    $result['insert_error'] = $e->getMessage();
                    Log::error("[PERIODIC_INSERT_ERROR] SOURCE={$source} | Error: " . $e->getMessage());
                }
            } else {
                $result['status'] = 'skipped_interval';
                Log::info(sprintf(
                    "[PERIODIC_SKIP] SOURCE=%s | Decision=%s | DiffMinutes=%s | SlotExists=%s",
                    $source,
                    $result['save_decision'],
                    (string)$result['difference_minutes'],
                    $alreadySavedInSlot ? 'YES' : 'NO'
                ));
            }
        });

        return $result;
    }
}
