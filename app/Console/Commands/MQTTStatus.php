<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\SensorReading;

class MQTTStatus extends Command
{
    protected $signature = 'mqtt:status';
    protected $description = 'Membaca status konfigurasi, cache, dan data sensor MQTT secara read-only';

    public function handle()
    {
        $this->info("=========================================================================================================");
        $this->info("                        DIAGNOSTIK READ-ONLY STATUS MQTT & SENSOR (SMARTFARM)                           ");
        $this->info("=========================================================================================================\n");

        // 1. APP_ENV & CACHE DRIVER
        $appEnv = env('APP_ENV', config('app.env', 'production'));
        $cacheDriver = config('cache.default', 'file');

        $this->line("<comment>1. LINGKUNGAN APLIKASI & DRIVER CACHE:</comment>");
        $this->line("   - APP_ENV      : <info>{$appEnv}</info>");
        $this->line("   - Cache Driver : <info>{$cacheDriver}</info>\n");

        // 2. CACHE IOT_LIVE_DATA
        $this->line("<comment>2. STATUS CACHE LIVE DATA ('iot_live_data'):</comment>");
        if (Cache::has('iot_live_data')) {
            $live = Cache::get('iot_live_data');
            $this->line("   - Status Alat      : <info>ONLINE</info>");
            $this->line("   - Suhu Udara       : " . ($live['suhu'] ?? '--') . " °C");
            $this->line("   - Kelembapan Udara : " . ($live['kelembapan_udara'] ?? '--') . " %");
            $this->line("   - Kelembapan Tanah : " . ($live['kelembapan_tanah'] ?? '--') . " %");
            $this->line("   - Nilai Fuzzy      : " . ($live['nilai_fuzzy'] ?? '--'));
            $this->line("   - Keputusan Sistem : " . ($live['keputusan_sistem'] ?? ($live['deteksi'] ?? '--')));
            $this->line("   - Updated At       : " . ($live['updated_at'] ?? '--'));
        } else {
            $this->warn("   - Status Alat      : OFFLINE (Cache 'iot_live_data' tidak ditemukan/expired)");
        }
        $this->line("");

        // 3. 15 RECORD TERAKHIR TABEL SENSOR_READINGS
        $this->line("<comment>3. LIMA BELAS (15) RECORD SENSOR_READINGS TERBARU:</comment>");
        $records = SensorReading::orderBy('id', 'desc')->take(15)->get();

        if ($records->isNotEmpty()) {
            $this->table(
                ['ID', 'Created At (detik)', 'Suhu (°C)', 'Udara (%)', 'Tanah (%)', 'Fuzzy', 'YOLO', 'Image', 'Keputusan'],
                $records->map(function ($r) {
                    return [
                        $r->id,
                        $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '-',
                        $r->suhu ?? 0,
                        $r->kelembapan_udara ?? 0,
                        $r->kelembapan_tanah ?? 0,
                        $r->nilai_fuzzy ? number_format($r->nilai_fuzzy, 4) : 0,
                        $r->deteksi_yolo ?? 'OFF',
                        $r->image ? 'AdaFoto' : 'NoFoto',
                        $r->deteksi ?? 'AMAN',
                    ];
                })
            );
        } else {
            $this->warn("   [!] Tidak ada record dalam tabel sensor_readings.\n");
        }

        // 4. KELOMPOK RECORD SENSOR PERIODIK DALAM SLOT 15 MENIT YANG SAMA
        $this->line("\n<comment>4. AUDIT SLOT 15 MENIT PENYIMPANAN SENSOR PERIODIK:</comment>");
        $periodicRecords = SensorReading::where(function ($q) {
            $q->whereNull('deteksi_yolo')->orWhere('deteksi_yolo', 'OFF');
        })->orderBy('id', 'desc')->take(20)->get();

        if ($periodicRecords->isNotEmpty()) {
            $slots = [];
            foreach ($periodicRecords as $pr) {
                $ca = $pr->created_at;
                if (!$ca) continue;

                $minuteSlot = floor($ca->minute / 15) * 15;
                $slotStart = $ca->copy()->minute((int)$minuteSlot)->second(0);
                $slotEnd   = $slotStart->copy()->addMinutes(15)->subSecond();

                $slotKey = $slotStart->format('Y-m-d H:i');

                if (!isset($slots[$slotKey])) {
                    $slots[$slotKey] = [
                        'slot' => $slotStart->format('Y-m-d H:i:s') . ' s.d ' . $slotEnd->format('H:i:s'),
                        'count' => 0,
                        'ids' => [],
                    ];
                }
                $slots[$slotKey]['count']++;
                $slots[$slotKey]['ids'][] = $pr->id;
            }

            $tableData = [];
            foreach ($slots as $sKey => $sVal) {
                $statusFlag = ($sVal['count'] === 1) ? '✅ Normal (1 record)' : '⚠️ Duplikat (' . $sVal['count'] . ' records)';
                $tableData[] = [
                    $sVal['slot'],
                    $sVal['count'],
                    implode(', ', $sVal['ids']),
                    $statusFlag,
                ];
            }

            $this->table(
                ['Rentang Slot 15-Menit', 'Jumlah Record', 'Daftar ID Record', 'Status Audit'],
                $tableData
            );
        } else {
            $this->warn("   [!] Tidak ada record sensor periodik ditemukan.");
        }

        // 5. TEST ATOMIC LOCK (READ-ONLY GET & IMMEDIATE RELEASE)
        $this->line("\n<comment>5. UJI INTEGRITAS ATOMIC LOCK ('sensor_periodic_db_save_lock'):</comment>");
        try {
            $lock = Cache::lock('sensor_periodic_db_save_lock', 5);
            if ($lock->get()) {
                $this->info("   ✅ BERHASIL: Atomic lock dapat diperoleh dan langsung dilepaskan secara aman.");
                $lock->release();
            } else {
                $this->warn("   ⚠️ PERINGATAN: Lock sedang dipegang oleh proses subscriber lain yang sedang berjalan.");
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ ERROR ATOMIC LOCK: " . $e->getMessage());
        }

        $this->info("\n=========================================================================================================");
        $this->info("                              DIAGNOSTIK TERSELESAIKAN SECARA AMAN                                       ");
        $this->info("=========================================================================================================");

        return Command::SUCCESS;
    }
}
