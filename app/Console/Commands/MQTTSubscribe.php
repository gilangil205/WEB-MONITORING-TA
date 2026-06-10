<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\SensorReading;
use Illuminate\Support\Facades\Cache; // 💡 TAMBAHAN

class MQTTSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe MQTT dari ESP32';

    public function handle()
    {
        $server   = env('MQTT_HOST');
        $port     = env('MQTT_PORT');
        $clientId = env('MQTT_CLIENT_ID') . uniqid();

        $connectionSettings = (new ConnectionSettings)
                ->setKeepAliveInterval(10) 
                ->setConnectTimeout(3);
        $mqtt = new MqttClient($server, $port, $clientId);

        $mqtt->connect($connectionSettings, true);

        $this->info('MQTT Subscriber berjalan...');

        $mqtt->subscribe('priyatna/deteksi/data', function ($topic, $message) {

            $this->info("Pesan masuk: " . $message);
            $data = json_decode($message, true);

            if (!$data) {
                $this->error('JSON tidak valid');
                return;
            }

            $suhu = $data['suhu_udara'] ?? 0;
            $udara = $data['kelembapan_udara'] ?? 0;
            $tanah = $data['kelembapan_tanah'] ?? 0;
            $deteksi = strtoupper($data['status'] ?? 'AMAN');
            $nilai_fuzzy = $data['nilai_fuzzy'] ?? 0;

            // Susun array data untuk disimpan di Cache live monitoring
            $cacheData = [
                'suhu'             => $suhu,
                'kelembapan_udara' => $udara,
                'kelembapan_tanah' => $tanah,
                'nilai_fuzzy'      => round($nilai_fuzzy, 4),
                'deteksi'          => $deteksi,
                'image'            => null, // MQTT teks json default tanpa base64 image
                'updated_at'       => now()->toIso8601String()
            ];

            // 💡 REVISI POIN 4: Simpan ke Cache setiap data MQTT masuk (Validasi Alat Online per 5 Menit)
            Cache::put('iot_live_data', $cacheData, now()->addMinutes(7));
            $this->info('Cache real-time berhasil diperbarui.');

            // 💡 REVISI POIN 5: Simpan ke Database dibatasi setiap interval 15 menit menggunakan Cooldown Lock
            if (!Cache::has('iot_db_cooldown')) {
                SensorReading::create([
                    'suhu'             => $suhu,
                    'kelembapan_udara' => $udara,
                    'kelembapan_tanah' => $tanah,
                    'deteksi'          => $deteksi,
                    'nilai_fuzzy'      => $nilai_fuzzy,
                ]);

                // Kunci penyimpanan database selama 14 menit ke depan
                Cache::put('iot_db_cooldown', true, now()->addMinutes(14));
                $this->info('Data BERHASIL disimpan ke Database (Interval 15 Menit)!');
            } else {
                $this->info('Penyimpanan DB dilewati (Masih dalam rentang cooldown 15 menit).');
            }

        }, 0);

        $mqtt->loop(true);
    }
}