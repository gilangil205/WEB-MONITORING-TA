<?php

namespace Tests;

use App\Models\SensorReading;
use App\Models\ThresholdSetting;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=========================================================================================================\n";
echo "           UJI COMPREHENSIVE 6 SKENARIO FRESHNESS YOLO MQTT SUBSCRIBER                                  \n";
echo "=========================================================================================================\n\n";

Cache::flush();

// Setup threshold default
ThresholdSetting::where('key', 'threshold_hama')->update(['value' => 0.70]);
ThresholdSetting::where('key', 'threshold_waspada')->update(['value' => 0.45]);
ThresholdSetting::clearCache();

$controller = app(\App\Http\Controllers\SensorController::class);
$suhu = 26.0; $udara = 60.0; $tanah = 60.0; // Fuzzy rendah (0.20 -> RENDAH)
$nilaiFuzzy = 0.20;
$deteksiFuzzy = 'RENDAH';

// Helper pemroses logika MQTT Subscriber
$processMqttYoloLogic = function () use ($controller, $deteksiFuzzy) {
    $inputDeteksiYolo = null;
    $inputConfidenceYolo = null;
    $hasilDeteksiYolo = 'DATA TIDAK TERSEDIA';

    $latestYoloUpdatedAt = Cache::get('latest_yolo_updated_at');
    if (!$latestYoloUpdatedAt && Cache::has('yolo_live_data')) {
        $yoloData = Cache::get('yolo_live_data');
        $latestYoloUpdatedAt = $yoloData['updated_at'] ?? null;
    }

    $isYoloFresh = false;
    if ($latestYoloUpdatedAt) {
        try {
            $parsedTime = \Carbon\Carbon::parse($latestYoloUpdatedAt);
            $isYoloFresh = $parsedTime->diffInMinutes(now()) <= \App\Http\Controllers\SensorController::CAMERA_EXPIRATION_MINUTES;
        } catch (\Throwable $e) {
            $isYoloFresh = false;
        }
    }

    if ($isYoloFresh && Cache::has('yolo_live_data')) {
        $yolo = Cache::get('yolo_live_data');
        $inputDeteksiYolo = $yolo['deteksi_yolo'] ?? null;
        $inputConfidenceYolo = $yolo['confidence_yolo'] ?? null;
        $hasilDeteksiYolo = $yolo['hasil_deteksi_yolo'] ?? 'OFF';
    }

    $keputusanSistem = $controller->getSystemDecision($hasilDeteksiYolo, $deteksiFuzzy);

    return [
        'is_fresh'           => $isYoloFresh,
        'hasil_yolo'         => $hasilDeteksiYolo,
        'input_yolo'         => $inputDeteksiYolo,
        'confidence'         => $inputConfidenceYolo,
        'keputusan_sistem'   => $keputusanSistem,
    ];
};

// ----------------------------------------------------------------------------------
// SKENARIO 1: YOLO ON fresh < 2 menit -> keputusan HAMA
// ----------------------------------------------------------------------------------
Cache::flush();
Cache::put('yolo_live_data', ['deteksi_yolo' => 'ON', 'confidence_yolo' => 0.95, 'hasil_deteksi_yolo' => 'ON'], now()->addMinutes(15));
Cache::put('latest_yolo_updated_at', now()->subMinutes(1)->toIso8601String(), now()->addDays(7));

$res1 = $processMqttYoloLogic();
echo "1. YOLO ON Fresh (<2m):\n";
echo "   - Freshness: " . ($res1['is_fresh'] ? 'FRESH' : 'STALE') . " | Hasil YOLO: {$res1['hasil_yolo']} | Keputusan: {$res1['keputusan_sistem']}\n";
if ($res1['is_fresh'] && $res1['hasil_yolo'] === 'ON' && $res1['keputusan_sistem'] === 'HAMA') {
    echo "   Status: [PASS] (YOLO Fresh ON memicu HAMA)\n\n";
} else {
    echo "   Status: [FAIL]\n\n";
    exit(1);
}

// ----------------------------------------------------------------------------------
// SKENARIO 2: YOLO OFF fresh < 2 menit -> keputusan mengikuti Fuzzy Sugeno (RENDAH)
// ----------------------------------------------------------------------------------
Cache::flush();
Cache::put('yolo_live_data', ['deteksi_yolo' => 'OFF', 'confidence_yolo' => 0.0, 'hasil_deteksi_yolo' => 'OFF'], now()->addMinutes(15));
Cache::put('latest_yolo_updated_at', now()->subMinutes(1)->toIso8601String(), now()->addDays(7));

$res2 = $processMqttYoloLogic();
echo "2. YOLO OFF Fresh (<2m):\n";
echo "   - Freshness: " . ($res2['is_fresh'] ? 'FRESH' : 'STALE') . " | Hasil YOLO: {$res2['hasil_yolo']} | Keputusan: {$res2['keputusan_sistem']}\n";
if ($res2['is_fresh'] && $res2['hasil_yolo'] === 'OFF' && $res2['keputusan_sistem'] === 'RENDAH') {
    echo "   Status: [PASS] (YOLO Fresh OFF mengikuti Fuzzy Sugeno)\n\n";
} else {
    echo "   Status: [FAIL]\n\n";
    exit(1);
}

// ----------------------------------------------------------------------------------
// SKENARIO 3: YOLO ON kedaluwarsa > 2 menit -> tidak memicu HAMA lama & hasil DATA TIDAK TERSEDIA
// ----------------------------------------------------------------------------------
Cache::flush();
Cache::put('yolo_live_data', ['deteksi_yolo' => 'ON', 'confidence_yolo' => 0.95, 'hasil_deteksi_yolo' => 'ON'], now()->addMinutes(15));
Cache::put('latest_yolo_updated_at', now()->subMinutes(10)->toIso8601String(), now()->addDays(7));

$res3 = $processMqttYoloLogic();
echo "3. YOLO ON Kedaluwarsa (>2m):\n";
echo "   - Freshness: " . ($res3['is_fresh'] ? 'FRESH' : 'STALE') . " | Hasil YOLO: {$res3['hasil_yolo']} | Keputusan: {$res3['keputusan_sistem']}\n";
if (!$res3['is_fresh'] && $res3['hasil_yolo'] === 'DATA TIDAK TERSEDIA' && $res3['keputusan_sistem'] === 'RENDAH') {
    echo "   Status: [PASS] (YOLO Kedaluwarsa TIDAK memicu HAMA lama & kembali ke Fuzzy Sugeno)\n\n";
} else {
    echo "   Status: [FAIL]\n\n";
    exit(1);
}

// ----------------------------------------------------------------------------------
// SKENARIO 4: Cache YOLO tersedia tetapi timestamp TIDAK TERSEDIA
// ----------------------------------------------------------------------------------
Cache::flush();
Cache::put('yolo_live_data', ['deteksi_yolo' => 'ON', 'confidence_yolo' => 0.95, 'hasil_deteksi_yolo' => 'ON'], now()->addMinutes(15));

$res4 = $processMqttYoloLogic();
echo "4. Cache YOLO Ada Tanpa Timestamp:\n";
echo "   - Freshness: " . ($res4['is_fresh'] ? 'FRESH' : 'STALE') . " | Hasil YOLO: {$res4['hasil_yolo']} | Keputusan: {$res4['keputusan_sistem']}\n";
if (!$res4['is_fresh'] && $res4['hasil_yolo'] === 'DATA TIDAK TERSEDIA' && $res4['keputusan_sistem'] === 'RENDAH') {
    echo "   Status: [PASS] (Status YOLO lama diabaikan jika tanpa timestamp)\n\n";
} else {
    echo "   Status: [FAIL]\n\n";
    exit(1);
}

// ----------------------------------------------------------------------------------
// SKENARIO 5: Timestamp YOLO TIDAK VALID (corrupt string) -> aman tanpa exception & ikuti Fuzzy
// ----------------------------------------------------------------------------------
Cache::flush();
Cache::put('yolo_live_data', ['deteksi_yolo' => 'ON', 'confidence_yolo' => 0.95, 'hasil_deteksi_yolo' => 'ON'], now()->addMinutes(15));
Cache::put('latest_yolo_updated_at', 'CORRUPT_TIMESTAMP_STRING_123', now()->addDays(7));

$res5 = $processMqttYoloLogic();
echo "5. Timestamp YOLO Tidak Valid ('CORRUPT_TIMESTAMP_STRING_123'):\n";
echo "   - Freshness: " . ($res5['is_fresh'] ? 'FRESH' : 'STALE') . " | Hasil YOLO: {$res5['hasil_yolo']} | Keputusan: {$res5['keputusan_sistem']}\n";
if (!$res5['is_fresh'] && $res5['hasil_yolo'] === 'DATA TIDAK TERSEDIA' && $res5['keputusan_sistem'] === 'RENDAH') {
    echo "   Status: [PASS] (Timestamp rusak ditangani aman tanpa exception & keputusan mengikuti Fuzzy)\n\n";
} else {
    echo "   Status: [FAIL]\n\n";
    exit(1);
}

// ----------------------------------------------------------------------------------
// SKENARIO 6: YOLO tidak tersedia (kosong) -> Cache live data & savePeriodicIfDue aman berjalan
// ----------------------------------------------------------------------------------
Cache::flush();
$res6 = $processMqttYoloLogic();

// Simpan data live & panggil service periodik
$cacheData = [
    'suhu'             => $suhu,
    'kelembapan_udara' => $udara,
    'kelembapan_tanah' => $tanah,
    'nilai_fuzzy'      => round($nilaiFuzzy, 4),
    'deteksi'          => $res6['keputusan_sistem'],
    'updated_at'       => now()->toIso8601String(),
];
Cache::put('iot_live_data', $cacheData, now()->addMinutes(7));

$saveRes = \App\Services\SensorHistoryService::savePeriodicIfDue(
    $suhu, $udara, $tanah, $nilaiFuzzy, $res6['keputusan_sistem'], null, null, null, 'MQTT'
);

echo "6. YOLO Tidak Tersedia (Kosong):\n";
echo "   - Live Cache Created: " . (Cache::has('iot_live_data') ? 'YES' : 'NO') . "\n";
echo "   - Periodic Save Status: " . ($saveRes['status'] ?? 'NONE') . "\n";
echo "   - Save Decision: " . ($saveRes['save_decision'] ?? 'NONE') . "\n";
if (Cache::has('iot_live_data') && isset($saveRes['status'])) {
    echo "   Status: [PASS] (Cache iot_live_data diperbarui & savePeriodicIfDue berhasil dipanggil)\n\n";
} else {
    echo "   Status: [FAIL]\n\n";
    exit(1);
}

echo "=========================================================================================================\n";
echo " ✅ SELURUH 6 SKENARIO REGRESI FRESHNESS MQTT SUBSCRIBER LULUS (6/6 PASS)\n";
echo "=========================================================================================================\n";
