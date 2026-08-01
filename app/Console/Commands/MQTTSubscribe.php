<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\SensorReading;
use App\Models\ThresholdSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\SensorController;
use App\Models\Notification;

class MQTTSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe MQTT dari ESP32';

    // ── Lebar transisi fuzzy — harus identik dengan SensorController ──────────
    private const T_SUHU   = 3;
    private const T_LEMBAP = 5;

    public function handle()
    {
        $server   = env('MQTT_HOST');
        $port     = (int) env('MQTT_PORT', 8883);
        $username = env('MQTT_USERNAME');
        $password = env('MQTT_PASSWORD');
        $useTls   = filter_var(env('MQTT_USE_TLS', true), FILTER_VALIDATE_BOOLEAN);

        $this->info('MQTT Subscriber Service berjalan...');

        while (true) {
            $clientId = env('MQTT_CLIENT_ID', 'laravel-server-') . '-' . uniqid();

                try {
                    $connectionSettings = (new ConnectionSettings)
                        ->setKeepAliveInterval(10)
                        ->setConnectTimeout(10)
                        ->setUsername($username)
                        ->setPassword($password)
                        ->setUseTls($useTls)
                        ->setTlsVerifyPeer(true)
                        ->setTlsSelfSignedAllowed(false);

                    $mqtt = new MqttClient($server, $port, $clientId);
                    $this->info("Menghubungkan ke Broker MQTT ({$server}:{$port})...");
                    $mqtt->connect($connectionSettings, true);

                    $this->info("✅ MQTT Subscriber terhubung & mendengarkan topic 'priyatna/deteksi/data'...");

                    $mqtt->subscribe('priyatna/deteksi/data', function ($topic, $message) {
                        try {
                            $this->info("Pesan masuk pada topic [{$topic}]: " . $message);
                            $data = json_decode($message, true);

                            if (!$data || !is_array($data)) {
                                $this->error('⚠️ JSON tidak valid atau bukan array');
                                return;
                            }

                            // Dukung alias field
                            $suhu  = (float) ($data['suhu_udara']       ?? ($data['suhu']       ?? ($data['temperature'] ?? 0)));
                            $udara = (float) ($data['kelembapan_udara']  ?? ($data['kelembapan']  ?? ($data['humidity']    ?? 0)));
                            $tanah = (float) ($data['kelembapan_tanah']  ?? ($data['tanah']       ?? ($data['soil']        ?? 0)));

                            // ✅ Hitung fuzzy di server
                            $nilai_fuzzy = $this->fuzzySugeno($suhu, $udara, $tanah);
                            [$deteksi]   = $this->getStatus($nilai_fuzzy);

                            // ── BACA CACHE YOLO (Integrasi Decision Rule) ──
                            $inputDeteksiYolo = null;
                            $inputConfidenceYolo = null;
                            $hasilDeteksiYolo = 'OFF';

                            if (Cache::has('yolo_live_data')) {
                            $yolo = Cache::get('yolo_live_data');
                            $inputDeteksiYolo = $yolo['deteksi_yolo'] ?? null;
                            $inputConfidenceYolo = $yolo['confidence_yolo'] ?? null;
                            $hasilDeteksiYolo = $yolo['hasil_deteksi_yolo'] ?? 'OFF';
                        }

                        // Keputusan Sistem
                        $controller = app(\App\Http\Controllers\SensorController::class);
                        $keputusanSistem = $controller->getSystemDecision($hasilDeteksiYolo, $deteksi);

                        $this->info(sprintf(
                            'Fuzzy server-side → suhu:%.1f udara:%.1f tanah:%.1f → nilai:%.4f status:%s',
                            $suhu, $udara, $tanah, $nilai_fuzzy, $keputusanSistem
                        ));

                        // Susun data untuk Cache live monitoring
                        $cacheData = [
                            'suhu'             => $suhu,
                            'kelembapan_udara' => $udara,
                            'kelembapan_tanah' => $tanah,
                            'nilai_fuzzy'      => round($nilai_fuzzy, 4),
                            'deteksi'          => $keputusanSistem,
                            'deteksi_yolo'     => $inputDeteksiYolo,
                            'confidence_yolo'  => $inputConfidenceYolo,
                            'prediksi_sensor'  => $deteksi,
                            'hasil_deteksi_yolo' => $hasilDeteksiYolo,
                            'keputusan_sistem' => $keputusanSistem,
                            'image'            => null,
                            'updated_at'       => now()->toIso8601String(),
                        ];

                        // Simpan ke Cache setiap data MQTT masuk (validasi alat online per 7 menit)
                        Cache::put('iot_live_data', $cacheData, now()->addMinutes(7));
                        $this->info('Cache real-time berhasil diperbarui.');

                        // Atomic Lock & 15-Minute Interval Check for DB Persistence (Shared Key with HTTP API)
                        $lockAcquired = Cache::lock('sensor_periodic_db_save_lock', 10)->get(function () use ($suhu, $udara, $tanah, $nilai_fuzzy, $inputDeteksiYolo, $inputConfidenceYolo, $keputusanSistem) {
                            // Source of truth: hanya ambil record sensor periodik (bukan insiden YOLO ON)
                            $latestHistori = SensorReading::where(function ($q) {
                                $q->whereNull('deteksi_yolo')->orWhere('deteksi_yolo', 'OFF');
                            })->latest()->first();

                            $shouldSave = true;
                            $selisihMenit = 999;

                            if ($latestHistori) {
                                $selisihMenit = $latestHistori->created_at->diffInMinutes(now());
                                if ($selisihMenit < 15) {
                                    $shouldSave = false;
                                }
                            }

                            if ($shouldSave) {
                                $permanentPath = null;
                                if (\Illuminate\Support\Facades\Storage::disk('public')->exists('kamera/latest_live.jpg')) {
                                    $permanentFilename = 'kamera/periodic_' . time() . '_' . uniqid() . '.jpg';
                                    \Illuminate\Support\Facades\Storage::disk('public')->copy('kamera/latest_live.jpg', $permanentFilename);
                                    $permanentPath = $permanentFilename;
                                }

                                $sensor = SensorReading::create([
                                    'suhu'             => $suhu,
                                    'kelembapan_udara' => $udara,
                                    'kelembapan_tanah' => $tanah,
                                    'nilai_fuzzy'      => $nilai_fuzzy,
                                    'image'            => $permanentPath,
                                    'deteksi'          => $keputusanSistem,
                                    'deteksi_yolo'     => $inputDeteksiYolo,
                                    'confidence_yolo'  => $inputConfidenceYolo,
                                ]);

                                $this->info("Data BERHASIL disimpan ke Database (Interval 15 Menit)! ID: {$sensor->id}");

                                if (in_array($keputusanSistem, ['HAMA', 'TINGGI', 'SEDANG'])) {
                                    $lastNotif = Notification::where('status', $keputusanSistem)
                                                             ->where('created_at', '>=', now()->subMinutes(15))
                                                             ->first();

                                    if (!$lastNotif) {
                                        $controller = app(SensorController::class);
                                        $controller->createNotification($keputusanSistem, round($nilai_fuzzy, 4), $sensor);
                                        $this->info("Notifikasi {$keputusanSistem} dikirim!");
                                    }
                                }
                            } else {
                                $this->info("Penyimpanan DB dilewati (Selisih waktu: {$selisihMenit} menit < 15 menit).");
                            }
                        });

                        if (!$lockAcquired) {
                            $this->info('Penyimpanan DB dilewati (Lock sedang dipegang oleh proses subscriber lain).');
                        }

                    } catch (\Throwable $e) {
                        $this->error("⚠️ Error memproses payload MQTT: " . $e->getMessage());
                        Log::error("MQTT Payload Error: " . $e->getMessage());
                    }

                }, 0);

                $mqtt->loop(true);

            } catch (\Throwable $e) {
                $this->error("⚠️ MQTT Disconnected / Socket Exception: " . $e->getMessage());
                Log::warning("MQTT Subscriber Connection Lost: " . $e->getMessage());
                $this->info("Mencoba me-reconnect dalam 3 detik...");
                sleep(3);
            }
        }
    }

    // ==================================================================================
    // FUZZY SUGENO — duplikat dari SensorController agar MQTT command mandiri
    //
    // ⚠️  Jika suatu saat mengubah logika di SensorController::fuzzySugeno(),
    //     pastikan metode ini ikut diperbarui agar kedua jalur (HTTP & MQTT)
    //     menghasilkan nilai yang identik untuk input yang sama.
    // ==================================================================================
    private function fuzzySugeno(float $suhu, float $udara, float $tanah): float
    {
        // ── Baca 9 threshold dari DB (via cache 1 jam) ────────────────────────
        $sAman    = ThresholdSetting::getValue('suhu_aman',    22);
        $sWaspada = ThresholdSetting::getValue('suhu_waspada', 28);
        $sHama    = ThresholdSetting::getValue('suhu_hama',    32);

        $uAman    = ThresholdSetting::getValue('udara_aman',    60);
        $uWaspada = ThresholdSetting::getValue('udara_waspada', 75);
        $uHama    = ThresholdSetting::getValue('udara_hama',    85);

        $tAman    = ThresholdSetting::getValue('tanah_aman',    55);
        $tWaspada = ThresholdSetting::getValue('tanah_waspada', 68);
        $tHama    = ThresholdSetting::getValue('tanah_hama',    80);

        // ── TAHAP 1A: Membership SUHU ─────────────────────────────────────────
        $dingin = max(0, min(1, ($sAman    - $suhu) / self::T_SUHU));
        $panas  = max(0, min(1, ($suhu  - $sWaspada) / max(0.01, $sHama - $sWaspada)));
        $hangat = max(0, min(1, 1 - $dingin - $panas));

        // ── TAHAP 1B: Membership KELEMBAPAN UDARA ─────────────────────────────
        $kering_u = max(0, min(1, ($uAman    - $udara) / self::T_LEMBAP));
        $lembap_u = max(0, min(1, ($udara - $uWaspada) / max(0.01, $uHama - $uWaspada)));
        $normal_u = max(0, min(1, 1 - $kering_u - $lembap_u));

        // ── TAHAP 1C: Membership KELEMBAPAN TANAH ─────────────────────────────
        $kering_t = max(0, min(1, ($tAman    - $tanah) / self::T_LEMBAP));
        $lembap_t = max(0, min(1, ($tanah - $tWaspada) / max(0.01, $tHama - $tWaspada)));
        $normal_t = max(0, min(1, 1 - $kering_t - $lembap_t));

        // ── TAHAP 2: EVALUASI 27 RULES ────────────────────────────────────────
        $rules = [
            // Suhu PANAS
            [min($panas,  $lembap_u, $lembap_t), 1.00],
            [min($panas,  $lembap_u, $normal_t), 0.85],
            [min($panas,  $lembap_u, $kering_t), 0.75],
            [min($panas,  $normal_u, $lembap_t), 0.70],
            [min($panas,  $normal_u, $normal_t), 0.55],
            [min($panas,  $normal_u, $kering_t), 0.45],
            [min($panas,  $kering_u, $lembap_t), 0.50],
            [min($panas,  $kering_u, $normal_t), 0.40],
            [min($panas,  $kering_u, $kering_t), 0.30],
            // Suhu HANGAT
            [min($hangat, $lembap_u, $lembap_t), 0.80],
            [min($hangat, $lembap_u, $normal_t), 0.65],
            [min($hangat, $lembap_u, $kering_t), 0.55],
            [min($hangat, $normal_u, $lembap_t), 0.50],
            [min($hangat, $normal_u, $normal_t), 0.40],
            [min($hangat, $normal_u, $kering_t), 0.30],
            [min($hangat, $kering_u, $lembap_t), 0.35],
            [min($hangat, $kering_u, $normal_t), 0.25],
            [min($hangat, $kering_u, $kering_t), 0.20],
            // Suhu DINGIN
            [min($dingin, $lembap_u, $lembap_t), 0.45],
            [min($dingin, $lembap_u, $normal_t), 0.35],
            [min($dingin, $lembap_u, $kering_t), 0.25],
            [min($dingin, $normal_u, $lembap_t), 0.30],
            [min($dingin, $normal_u, $normal_t), 0.20],
            [min($dingin, $normal_u, $kering_t), 0.15],
            [min($dingin, $kering_u, $lembap_t), 0.20],
            [min($dingin, $kering_u, $normal_t), 0.15],
            [min($dingin, $kering_u, $kering_t), 0.10],
        ];

        // ── TAHAP 3: DEFUZZIFIKASI SUGENO (Weighted Average) ─────────────────
        $num = $den = 0;
        foreach ($rules as [$r, $z]) {
            $num += $r * $z;
            $den += $r;
        }

        if ($den == 0) {
            Log::warning("MQTT Fuzzy: firing_strength=0 — Suhu:{$suhu} Udara:{$udara} Tanah:{$tanah}");
            return 0;
        }

        return $num / $den;
    }

    // ── TAHAP 4: KEPUTUSAN ────────────────────────────────────────────────────
    private function getStatus(float $nilai): array
    {
        $th = ThresholdSetting::getValue('threshold_hama',    0.70);
        $tw = ThresholdSetting::getValue('threshold_waspada', 0.45);

        if ($nilai >= $th) return ['TINGGI', 'status-high'];
        if ($nilai >= $tw) return ['SEDANG', 'status-medium'];
        return                    ['RENDAH', 'status-low'];
    }
}
