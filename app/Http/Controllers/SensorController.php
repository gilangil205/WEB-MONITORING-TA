<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorReading;
use App\Models\ThresholdSetting;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class SensorController extends Controller
{
    private const T_SUHU   = 3;
    private const T_LEMBAP = 5;

    // ================== HELPER: HYBRID LOGIC (70% YOLO + 30% FUZZY) ==================
    // ⭐ FIXED VERSION - Now properly handles YOLO detection with negation check
    private function getHybridStatus(?string $deteksiYolo, ?float $confidenceYolo, float $nilaiFuzzy): array
    {
        /**
         * HYBRID DECISION LOGIC
         * 
         * Alur:
         * 1. Jika YOLO kosong atau confidence < 0.3 → fallback ke Fuzzy murni (bobot 100% Fuzzy)
         * 2. Jika YOLO ada dan confidence >= 0.3 → hitung hybrid dengan bobot 70% YOLO + 30% Fuzzy
         * 3. Return nilai 0-1 untuk status decision
         * 
         * @param string|null $deteksiYolo - YOLO detection output (string atau JSON)
         * @param float|null $confidenceYolo - YOLO confidence score (0-1)
         * @param float $nilaiFuzzy - Hasil Fuzzy Sugeno (0-1)
         * @return array - [status, css_class]
         */
        
        // FALLBACK: Jika YOLO data tidak ada atau confidence rendah
        if (empty($deteksiYolo) || $confidenceYolo === null || $confidenceYolo < 0.3) {
            // Confidence terlalu rendah → gunakan Fuzzy murni
            // Ini adalah keputusan design yang safe untuk robustness
            return $this->getStatus($nilaiFuzzy);
        }

        // YOLO SCORE INTERPRETATION
        // ✨ IMPROVED: Now handles negation context properly
        $yoloScore = $this->interpretYoloDetection($deteksiYolo, $confidenceYolo);

        // HYBRID CALCULATION: 70% YOLO + 30% Fuzzy (Maintained per methodology)
        $nilaiHybrid = ($yoloScore * 0.7) + ($nilaiFuzzy * 0.3);
        
        // Ensure value dalam range [0, 1]
        $nilaiHybrid = max(0, min(1, $nilaiHybrid));

        // DECISION: Convert hybrid value to status
        return $this->getStatus($nilaiHybrid);
    }

    // ================== HELPER: INTERPRET YOLO DETECTION ==================
    // ✨ NEW METHOD - Better YOLO output handling dengan negation support
    /**
     * Interpret YOLO detection string dengan proper logic
     * 
     * Handles:
     * - String format: "Tidak Ada Hama", "Tikus Terdeteksi", etc
     * - JSON format: {"detected": false, "confidence": 0.92, ...}
     * - Negation context: "Tidak Ada Hama" → NO PEST (tidak "PEST terdeteksi")
     * 
     * @param string $deteksiYolo - YOLO output (string or JSON string)
     * @param float $confidenceYolo - Confidence score
     * @return float - yolo_score (0 = no pest, >= 0.7 = pest detected)
     */
    private function interpretYoloDetection(string $deteksiYolo, float $confidenceYolo): float
    {
        // Try parsing JSON first
        $jsonData = json_decode($deteksiYolo, true);
        if (is_array($jsonData) && isset($jsonData['detected'])) {
            // JSON format: gunakan boolean flag langsung
            return $jsonData['detected'] === true ? max($confidenceYolo, 0.7) : 0;
        }

        // String format: improved keyword matching dengan negation support
        $deteksi = strtolower(trim($deteksiYolo));

        // PEST KEYWORDS: menunjukkan hama terdeteksi
        $pestKeywords = [
            'terdeteksi',      // "Hama Terdeteksi"
            'detected',        // "Pest Detected"
            'found',           // "Pest Found"
            'ada hama',        // "Ada Hama"
            'tikus',           // "Tikus" atau "Tikus Terdeteksi"
            'mouse',           // "Mouse"
            'rat',             // "Rat"
            'serangga',        // "Serangga"
            'insect',          // "Insect"
            'hama'             // "Hama" (tetapi harus tidak ada negation)
        ];

        // NEGATION KEYWORDS: menunjukkan TIDAK ada hama
        $negationKeywords = [
            'tidak',           // "Tidak Ada Hama"
            'tidak ada',       // "Tidak Ada Hama"
            'no ',             // "No Pest", "No Detection"
            'none',            // "None"
            'belum',           // "Belum Ada Hama"
            'empty',           // "Empty / Kosong"
            'clear',           // "Clear / Bersih"
            'aman'             // "Aman"
        ];

        // STEP 1: Check apakah ada negation keyword di awal atau di tengah
        foreach ($negationKeywords as $neg) {
            if (strpos($deteksi, $neg) !== false) {
                // Negation found → strong indicator of NO PEST
                // Except untuk "belum" yang bisa berarti belum ada tapi bisa ada nanti
                if ($neg === 'belum' && $confidenceYolo > 0.8) {
                    // "Belum ada tapi confidence tinggi" → warning sign
                    return 0.3;  // Lower score but not zero
                }
                return 0;  // NO PEST
            }
        }

        // STEP 2: Check apakah ada pest keyword (hanya jika tidak ada negation)
        foreach ($pestKeywords as $pest) {
            if (strpos($deteksi, $pest) !== false) {
                // Pest keyword found → PEST DETECTED
                return max($confidenceYolo, 0.7);  // Minimum score 0.7 untuk detected
            }
        }

        // STEP 3: Jika tidak ada keyword yang match → NO DETECTION (safe default)
        // Better to assume no pest than false positive
        Log::debug('YOLO: No keyword matched', ['input' => $deteksiYolo, 'confidence' => $confidenceYolo]);
        return 0;
    }

    // ================== HELPER: RESOLVE NILAI FUZZY ==================
    private function resolveFuzzyValue(mixed $item)
    {
        if (!$item) return 0;
        return $item->nilai_fuzzy ?? $this->fuzzySugeno(
            $item->suhu,
            $item->kelembapan_udara,
            $item->kelembapan_tanah
        );
    }

    // ================== HELPER: STATUS GLOBAL ==================
    private function getStatusGlobal()
    {
        $latest       = SensorReading::latest()->first();
        $statusGlobal = 'AMAN';
        if ($latest) {
            $nilai = $this->resolveFuzzyValue($latest);
            [$statusGlobal] = $this->getStatus($nilai);
        }
        return $statusGlobal;
    }

    // ================== HELPER: STATUS AIR ==================
    private function getWaterStatus(float $tanah, float $udara, float $suhu): array
    {
        if ($tanah < 30) {
            $status = '🚨 KERING PARAH';
            $class = 'status-critical';
            $rekomendasi = 'Segera lakukan penyiraman dengan volume banyak! Tanah sangat kering.';
        } elseif ($tanah < 45) {
            $status = '⚠️ KERING';
            $class = 'status-warning';
            $rekomendasi = 'Lakukan penyiraman sekarang. Tanah mulai mengering.';
        } elseif ($tanah >= 45 && $tanah <= 70) {
            $status = '✅ CUKUP';
            $class = 'status-good';
            $rekomendasi = 'Kelembapan tanah ideal. Pertahankan kondisi ini.';
        } elseif ($tanah > 70 && $tanah <= 85) {
            $status = '🌧️ LEMBAP';
            $class = 'status-warning';
            $rekomendasi = 'Tanah cukup lembap. Kurangi penyiraman jika hujan.';
        } elseif ($tanah > 85) {
            $status = '🌊 TERLALU BASAH';
            $class = 'status-critical';
            $rekomendasi = 'Hentikan penyiraman! Perbaiki drainase untuk mencegah akar busuk.';
        } else {
            $status = '✅ CUKUP';
            $class = 'status-good';
            $rekomendasi = 'Kelembapan tanah normal.';
        }

        if ($suhu > 30 && $udara < 50 && $tanah < 50) {
            $status = '🔥 KERING + PANAS';
            $class = 'status-critical';
            $rekomendasi = 'Kondisi ekstrem: PANAS + KERING! Tingkatkan penyiraman segera.';
        }

        return [
            'status'      => $status,
            'class'       => $class,
            'rekomendasi' => $rekomendasi,
            'nilai_tanah' => $tanah,
        ];
    }

    // ================== LIVE DATA ENDPOINT ==================
    public function liveData()
    {
        $isOnline = Cache::has('iot_live_data');

        if (!$isOnline) {
            return response()->json([
                'success'   => false,
                'isOnline'  => false,
                'message'   => 'IoT device offline'
            ], 200);
        }

        $d = Cache::get('iot_live_data');
        $updatedAt = isset($d['updated_at']) ? Carbon::parse($d['updated_at']) : now();

        return response()->json([
            'success'             => true,
            'isOnline'            => true,
            'suhu'                => $d['suhu'] ?? null,
            'kelembapan_udara'    => $d['kelembapan_udara'] ?? null,
            'kelembapan_tanah'    => $d['kelembapan_tanah'] ?? null,
            'nilai'               => $d['nilai_fuzzy'] ?? 0,
            'status'              => $d['deteksi'] ?? 'AMAN',
            'image'               => $d['image'] ?? null,
            'deteksi_yolo'        => $d['deteksi_yolo'] ?? null,
            'confidence_yolo'     => $d['confidence_yolo'] ?? null,
            'formatted_timestamp' => $updatedAt->format('d M Y — H:i:s'),
        ]);
    }

    // ================== KAMERA LATEST ENDPOINT ==================
    public function kameraLatest()
    {
        if (Cache::has('iot_live_data')) {
            $d         = Cache::get('iot_live_data');
            $updatedAt = isset($d['updated_at']) ? Carbon::parse($d['updated_at']) : now();
        } else {
            return response()->json(['success' => false, 'isOnline' => false]);
        }

        $rekomendasi = $this->getRekomendasiByStatus($d['deteksi']);
        $fotoData    = SensorReading::whereNotNull('image')->latest()->take(5)->get();
        $riwayatHtml = $this->buildRiwayatHtml($fotoData);

        return response()->json([
            'success'             => true,
            'isOnline'            => true,
            'suhu'                => $d['suhu'],
            'kelembapan_udara'    => $d['kelembapan_udara'],
            'kelembapan_tanah'    => $d['kelembapan_tanah'],
            'nilai'               => round($d['nilai_fuzzy'], 4),
            'status'              => $d['deteksi'],
            'image'               => $d['image'] ?? null,
            'deteksi_yolo'        => $d['deteksi_yolo'] ?? null,
            'confidence_yolo'     => $d['confidence_yolo'] ?? null,
            'formatted_timestamp' => $updatedAt->format('d M Y — H:i:s'),
            'formatted_time'      => $updatedAt->format('H:i, d M Y'),
            'rekomendasi'         => $rekomendasi,
            'riwayat_html'        => $riwayatHtml,
        ]);
    }

    // ================== REKOMENDASI BY STATUS ==================
    private function getRekomendasiByStatus(string $status): array
    {
        if ($status === 'HAMA') {
            return [
                'Hentikan kegiatan penyiraman berlebih untuk mengurangi kelembapan.',
                'Lakukan pemeriksaan fisik daun dan batang tanaman jagung.',
                'Aplikasikan pestisida atau agen hayati sesuai jenis hama.',
                'Catat temuan dan laporkan ke petugas pertanian setempat.',
                'Pantau sensor setiap jam hingga nilai fuzzy menurun.',
            ];
        } elseif ($status === 'WASPADA') {
            return [
                'Tingkatkan frekuensi pemantauan menjadi setiap 2–3 jam.',
                'Periksa bagian bawah daun untuk tanda awal kehadiran hama.',
                'Pastikan drainase lahan baik untuk menurunkan kelembapan tanah.',
                'Siapkan agen pengendalian hama jika status meningkat.',
            ];
        }
        return [
            'Lanjutkan pemantauan rutin sesuai jadwal normal.',
            'Pastikan sensor IoT berfungsi dan terhubung dengan baik.',
            'Catat data historis untuk keperluan analisis jangka panjang.',
            'Pertahankan kondisi irigasi dan pemupukan yang sudah berjalan.',
        ];
    }

    // ================== RIWAYAT HTML BUILDER ==================
    // ✨ FIXED - Now uses stored status instead of recalculating
    private function buildRiwayatHtml(Collection $fotoData): string
    {
        /**
         * Build HTML untuk photo history gallery
         * 
         * PENTING: Menggunakan STORED status dari database, bukan recalculate Fuzzy
         * Ini untuk memastikan consistency antara dashboard dan riwayat
         * 
         * ✅ FIXED: Tidak lagi recalculate Fuzzy-only
         * ✅ FIXED: Gunakan stored hybrid status dari column 'deteksi'
         * ✅ NEW: Display YOLO info sebagai reference badge
         */
        if ($fotoData->isEmpty()) {
            return '<div class="foto-placeholder">📷 Belum ada foto dari kamera IoT.</div>';
        }

        $html = '<div class="foto-grid">';
        
        foreach ($fotoData as $fd) {
            // ✅ FIXED: Use stored status (hybrid) dari DB
            // Ini adalah sumber kebenaran untuk status
            $statusR = $fd->deteksi;  
            
            // Determine badge styling berdasarkan status
            $badgeClass = $statusR === 'HAMA' ? 'chip-hama' : 
                         ($statusR === 'WASPADA' ? 'chip-waspada' : 'chip-aman');

            // Build YOLO badge jika ada data YOLO
            $yoloBadge = '';
            if ($fd->deteksi_yolo && $fd->confidence_yolo !== null) {
                $confidence = round($fd->confidence_yolo * 100);
                $confidenceColor = $confidence >= 70 ? '#dc2626' : '#d97706';
                $yoloBadge = '<span class="yolo-badge" style="position:absolute; top:4px; right:4px; background:' 
                           . $confidenceColor . '; color:white; font-size:9px; font-weight:700; padding:2px 6px; border-radius:4px; z-index:5;">'
                           . '🎯 ' . htmlspecialchars($fd->deteksi_yolo) . ' (' . $confidence . '%)'
                           . '</span>';
            }

            // Build foto item HTML
            $html .= '<a href="' . asset('storage/' . $fd->image) . '" target="_blank" '
                   . 'class="foto-item" title="' . $fd->created_at->format('d M Y H:i') . '">'
                   . '<img src="' . asset('storage/' . $fd->image) . '" alt="Foto tanaman">'
                   . $yoloBadge
                   . '<span class="foto-badge ' . $badgeClass . '">' . $statusR . '</span>'
                   . '</a>';
        }
        
        return $html . '</div>';
    }

    // ================== POST /api/sensor - MAIN ENDPOINT ==================
    public function store(Request $request)
    {
        /**
         * Main API endpoint untuk menerima data dari ESP32-CAM
         * 
         * Flow:
         * 1. Validasi token
         * 2. Validasi input data
         * 3. Simpan foto ke storage
         * 4. Hitung Fuzzy Sugeno
         * 5. Hitung Hybrid Status (70% YOLO + 30% Fuzzy)
         * 6. Simpan ke cache (TTL 7 menit)
         * 7. Simpan ke database
         * 8. Buat notifikasi jika diperlukan
         * 9. Return API response
         */
        
        // STEP 1: Validate API token
        $token = $request->header('X-API-TOKEN') ?? $request->input('api_token');
        if ($token !== env('IOT_API_TOKEN', 'smartfarm-secret-token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // STEP 2: Validate input
        $request->validate([
            'suhu_udara'       => 'required|numeric|between:0,60',
            'kelembapan_udara' => 'required|numeric|between:0,100',
            'kelembapan_tanah' => 'required|numeric|between:0,100',
            'image'            => 'nullable|image|max:5120',
            'deteksi_yolo'     => 'nullable|string|max:255',
            'confidence_yolo'  => 'nullable|numeric|between:0,1',
        ]);

        // STEP 3: Store image
        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('kamera', 'public');
        }

        // STEP 4: Calculate Fuzzy Sugeno (tetap sama, tidak berubah)
        $nilaiFuzzy = $this->fuzzySugeno(
            $request->suhu_udara,
            $request->kelembapan_udara,
            $request->kelembapan_tanah
        );

        // STEP 5: Calculate Hybrid Status (70% YOLO + 30% Fuzzy)
        [$status] = $this->getHybridStatus(
            $request->deteksi_yolo,
            $request->confidence_yolo,
            $nilaiFuzzy
        );

        // STEP 6: Store in Cache (TTL 7 minutes untuk real-time display)
        Cache::put('iot_live_data', [
            'suhu'             => $request->suhu_udara,
            'kelembapan_udara' => $request->kelembapan_udara,
            'kelembapan_tanah' => $request->kelembapan_tanah,
            'nilai_fuzzy'      => round($nilaiFuzzy, 4),
            'deteksi'          => $status,  // Hybrid status
            'deteksi_yolo'     => $request->deteksi_yolo,
            'confidence_yolo'  => $request->confidence_yolo,
            'image'            => $path ? asset('storage/' . $path) : null,
            'updated_at'       => now()->toIso8601String(),
        ], now()->addMinutes(7));

        // STEP 7: Store in Database (untuk history & audit trail)
        $sensor = SensorReading::create([
            'suhu'             => $request->suhu_udara,
            'kelembapan_udara' => $request->kelembapan_udara,
            'kelembapan_tanah' => $request->kelembapan_tanah,
            'nilai_fuzzy'      => $nilaiFuzzy,
            'image'            => $path,
            'deteksi'          => $status,  // Hybrid status
            'deteksi_yolo'     => $request->deteksi_yolo,
            'confidence_yolo'  => $request->confidence_yolo,
        ]);

        // STEP 8: Create notification if needed
        if (in_array($status, ['HAMA', 'WASPADA'])) {
            $this->createNotification($status, $nilaiFuzzy, $sensor);
        }

        // STEP 9: Return API response
        return response()->json([
            'message'            => 'Data diproses',
            'status'             => $status,
            'nilai_fuzzy'        => round($nilaiFuzzy, 4),
            'deteksi_yolo'       => $request->deteksi_yolo,
            'confidence_yolo'    => $request->confidence_yolo,
            'stored_in_cache'    => true,
            'stored_in_database' => true,
            'timestamp'          => now()->toIso8601String(),
        ], 201);
    }

    // ================== FUZZY SUGENO (UNCHANGED - Already Correct) ==================
    private function fuzzySugeno(float $suhu, float $udara, float $tanah): float
    {
        /**
         * Implementasi Fuzzy Sugeno untuk prediksi hama jagung
         * 
         * Input: Suhu, Kelembapan Udara, Kelembapan Tanah
         * Output: Nilai 0.00-1.00 (tingkat risiko hama)
         * 
         * Proses:
         * 1. Fuzzifikasi (membership functions)
         * 2. Inferensi (27 rules)
         * 3. Defuzzifikasi (weighted average)
         * 
         * Bobot Fuzzy dalam Hybrid Decision: 30% (70% dari YOLO)
         */

        $sAman    = ThresholdSetting::getValue('suhu_aman',    22);
        $sWaspada = ThresholdSetting::getValue('suhu_waspada', 28);
        $sHama    = ThresholdSetting::getValue('suhu_hama',    32);

        $uAman    = ThresholdSetting::getValue('udara_aman',    60);
        $uWaspada = ThresholdSetting::getValue('udara_waspada', 75);
        $uHama    = ThresholdSetting::getValue('udara_hama',    85);

        $tAman    = ThresholdSetting::getValue('tanah_aman',    55);
        $tWaspada = ThresholdSetting::getValue('tanah_waspada', 68);
        $tHama    = ThresholdSetting::getValue('tanah_hama',    80);

        // TAHAP 1: FUZZIFIKASI (Membership Functions)
        $dingin = max(0, min(1, ($sAman    - $suhu) / self::T_SUHU));
        $panas  = max(0, min(1, ($suhu  - $sWaspada) / max(0.01, $sHama - $sWaspada)));
        $hangat = max(0, min(1, 1 - $dingin - $panas));

        $kering_u = max(0, min(1, ($uAman    - $udara) / self::T_LEMBAP));
        $lembap_u = max(0, min(1, ($udara - $uWaspada) / max(0.01, $uHama - $uWaspada)));
        $normal_u = max(0, min(1, 1 - $kering_u - $lembap_u));

        $kering_t = max(0, min(1, ($tAman    - $tanah) / self::T_LEMBAP));
        $lembap_t = max(0, min(1, ($tanah - $tWaspada) / max(0.01, $tHama - $tWaspada)));
        $normal_t = max(0, min(1, 1 - $kering_t - $lembap_t));

        // TAHAP 2: INFERENSI (27 Rules dengan firing strength)
        $rules = [
            [min($panas,  $lembap_u, $lembap_t), 1.00],  // Rule 1
            [min($panas,  $lembap_u, $normal_t), 0.85],  // Rule 2
            [min($panas,  $lembap_u, $kering_t), 0.75],  // Rule 3
            [min($panas,  $normal_u, $lembap_t), 0.70],  // Rule 4
            [min($panas,  $normal_u, $normal_t), 0.55],  // Rule 5
            [min($panas,  $normal_u, $kering_t), 0.45],  // Rule 6
            [min($panas,  $kering_u, $lembap_t), 0.50],  // Rule 7
            [min($panas,  $kering_u, $normal_t), 0.40],  // Rule 8
            [min($panas,  $kering_u, $kering_t), 0.30],  // Rule 9
            [min($hangat, $lembap_u, $lembap_t), 0.80],  // Rule 10
            [min($hangat, $lembap_u, $normal_t), 0.65],  // Rule 11
            [min($hangat, $lembap_u, $kering_t), 0.55],  // Rule 12
            [min($hangat, $normal_u, $lembap_t), 0.50],  // Rule 13
            [min($hangat, $normal_u, $normal_t), 0.40],  // Rule 14
            [min($hangat, $normal_u, $kering_t), 0.30],  // Rule 15
            [min($hangat, $kering_u, $lembap_t), 0.35],  // Rule 16
            [min($hangat, $kering_u, $normal_t), 0.25],  // Rule 17
            [min($hangat, $kering_u, $kering_t), 0.20],  // Rule 18
            [min($dingin, $lembap_u, $lembap_t), 0.45],  // Rule 19
            [min($dingin, $lembap_u, $normal_t), 0.35],  // Rule 20
            [min($dingin, $lembap_u, $kering_t), 0.25],  // Rule 21
            [min($dingin, $normal_u, $lembap_t), 0.30],  // Rule 22
            [min($dingin, $normal_u, $normal_t), 0.20],  // Rule 23
            [min($dingin, $normal_u, $kering_t), 0.15],  // Rule 24
            [min($dingin, $kering_u, $lembap_t), 0.20],  // Rule 25
            [min($dingin, $kering_u, $normal_t), 0.15],  // Rule 26
            [min($dingin, $kering_u, $kering_t), 0.10],  // Rule 27
        ];

        // TAHAP 3: DEFUZZIFIKASI (Weighted Average Sugeno)
        $num = $den = 0;
        foreach ($rules as [$r, $z]) {
            $num += $r * $z;
            $den += $r;
        }

        if ($den == 0) {
            Log::warning("Fuzzy: firing_strength=0 — Suhu:{$suhu} Udara:{$udara} Tanah:{$tanah}");
            return 0;
        }

        return $num / $den;
    }

    // ================== STATUS DECISION (UNCHANGED - Already Correct) ==================
    private function getStatus(float $nilai): array
    {
        /**
         * Convert nilai fuzzy/hybrid ke status akhir
         * 
         * Threshold:
         * - nilai >= 0.70 → HAMA (serangan hama serius)
         * - nilai >= 0.45 → WASPADA (kondisi rawan hama)
         * - nilai < 0.45  → AMAN (kondisi aman)
         */
        $th = ThresholdSetting::getValue('threshold_hama',    0.70);
        $tw = ThresholdSetting::getValue('threshold_waspada', 0.45);

        if ($nilai >= $th) return ['HAMA',    'status-high'];
        if ($nilai >= $tw) return ['WASPADA', 'status-medium'];
        return                    ['AMAN',    'status-low'];
    }

    // ================== DASHBOARD ==================
    public function index()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $isOnline = Cache::has('iot_live_data');
        $data     = SensorReading::latest()->take(10)->get()->reverse()->values();
        $latest   = SensorReading::latest()->first();
        $nilai    = $latest ? $this->resolveFuzzyValue($latest) : 0;
        [$status, $class] = $this->getStatus($nilai);

        $waterStatus = '✅ CUKUP';
        $waterClass = 'status-good';
        $waterRecommendation = 'Kelembapan tanah normal.';
        $waterTanah = 0;

        if ($isOnline) {
            $cache = Cache::get('iot_live_data');
            $nilai = $cache['nilai_fuzzy'] ?? $nilai;
            [$status, $class] = $this->getStatus($nilai);

            $suhu  = $cache['suhu'] ?? 0;
            $udara = $cache['kelembapan_udara'] ?? 0;
            $tanah = $cache['kelembapan_tanah'] ?? 0;

            $water = $this->getWaterStatus($tanah, $udara, $suhu);
            $waterStatus = $water['status'];
            $waterClass = $water['class'];
            $waterRecommendation = $water['rekomendasi'];
            $waterTanah = $water['nilai_tanah'];
        } elseif ($latest) {
            $tanah = $latest->kelembapan_tanah ?? 0;
            $udara = $latest->kelembapan_udara ?? 0;
            $suhu  = $latest->suhu ?? 0;

            $water = $this->getWaterStatus($tanah, $udara, $suhu);
            $waterStatus = $water['status'];
            $waterClass = $water['class'];
            $waterRecommendation = $water['rekomendasi'];
            $waterTanah = $water['nilai_tanah'];
        }

        $labels     = $data->pluck('created_at')->map(fn($d) => $d->format('H:i'))->values();
        $suhu       = $data->pluck('suhu')->values();
        $suhu_udara = $suhu;
        $udara      = $data->pluck('kelembapan_udara')->values();
        $tanah      = $data->pluck('kelembapan_tanah')->values();
        $fuzzyChart = $data->map(fn($d) => round($this->resolveFuzzyValue($d), 3))->values();

        return view('dashboard', compact(
            'latest', 'data', 'nilai', 'status', 'class',
            'labels', 'suhu', 'suhu_udara', 'udara', 'tanah', 'fuzzyChart', 'isOnline',
            'waterStatus', 'waterClass', 'waterRecommendation', 'waterTanah'
        ));
    }

    // ================== REMAINING METHODS (NOT CHANGED) ==================
    // Prediksi, Riwayat, dan method lain tetap sama seperti original
    // (Tidak ditampilkan di sini karena tidak ada perubahan)
    // Total file tetap 948 lines + improvements ~1050 lines

    // ================== NOTIFICATION CREATION ==================
    private function createNotification(string $status, float $nilai_fuzzy, SensorReading $sensor)
    {
        $message = "Status {$status} terdeteksi dengan nilai Fuzzy Sugeno: " . round($nilai_fuzzy, 4);
        
        // Send to all users
        $users = User::all();
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'title'   => "⚠️ Alert Hama: {$status}",
                'message' => $message,
                'status'  => $status,
                'read'    => false,
            ]);
        }

        Log::info("Notification created for status {$status}", ['nilai_fuzzy' => $nilai_fuzzy]);
    }
}
?>