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
    private function getHybridStatus(?string $deteksiYolo, ?float $confidenceYolo, float $nilaiFuzzy): array
    {
        if (empty($deteksiYolo) || $confidenceYolo === null || $confidenceYolo < 0.3) {
            return $this->getStatus($nilaiFuzzy);
        }

        $isDetected = stripos($deteksiYolo, 'terdeteksi') !== false ||
                      stripos($deteksiYolo, 'detected') !== false ||
                      stripos($deteksiYolo, 'hama') !== false ||
                      stripos($deteksiYolo, 'tikus') !== false;

        if ($isDetected) {
            $yoloScore = max($confidenceYolo, 0.7);
        } else {
            $yoloScore = 0;
        }

        $nilaiHybrid = ($yoloScore * 0.7) + ($nilaiFuzzy * 0.3);
        $nilaiHybrid = max(0, min(1, $nilaiHybrid));

        return $this->getStatus($nilaiHybrid);
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
            $rekomendasi = 'Kondisi panas dan udara kering mempercepat penguapan. Segera siram!';
        } elseif ($suhu > 30 && $tanah < 60) {
            $status = '☀️ KERING & PANAS';
            $class = 'status-warning';
            $rekomendasi = 'Suhu tinggi. Periksa kelembapan tanah dan siram jika perlu.';
        } elseif ($suhu < 20 && $tanah > 75) {
            $status = '🥶 DINGIN & BASAH';
            $class = 'status-warning';
            $rekomendasi = 'Suhu rendah dan tanah basah. Kurangi penyiraman.';
        }

        return [
            'status' => $status,
            'class' => $class,
            'rekomendasi' => $rekomendasi,
            'nilai_tanah' => $tanah,
        ];
    }

    // ================== HELPER: BUAT NOTIFIKASI ==================
    private function createNotification(string $status, float $nilai, ?SensorReading $sensor = null)
    {
        $users = User::whereIn('role', ['user', 'admin'])->get();

        if ($status === 'HAMA') {
            $title = '🚨 Peringatan Hama Terdeteksi!';
            $message = "Sistem mendeteksi risiko serangan hama tinggi dengan nilai hybrid {$nilai}. Segera periksa kondisi tanaman jagung Anda.";
        } elseif ($status === 'WASPADA') {
            $title = '⚠️ Status Waspada Hama';
            $message = "Kondisi lingkungan mulai mengarah ke risiko hama (nilai hybrid {$nilai}). Tingkatkan frekuensi monitoring.";
        } else {
            return;
        }

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'status' => $status,
                'fuzzy_value' => $nilai,
                'sensor_reading_id' => $sensor?->id,
                'is_read' => false,
            ]);
        }
    }

    // ================== ENDPOINT: /live-data ==================
    public function liveData()
    {
        if (Cache::has('iot_live_data')) {
            $d         = Cache::get('iot_live_data');
            $updatedAt = isset($d['updated_at']) ? Carbon::parse($d['updated_at']) : now();

            return response()->json([
                'success'             => true,
                'isOnline'            => true,
                'device'              => 'ONLINE',
                'suhu'                => $d['suhu']             ?? null,
                'kelembapan_udara'    => $d['kelembapan_udara'] ?? null,
                'kelembapan_tanah'    => $d['kelembapan_tanah'] ?? null,
                'nilai_fuzzy'         => $d['nilai_fuzzy']      ?? 0,
                'status_hama'         => $d['deteksi']          ?? 'AMAN',
                'deteksi'             => $d['deteksi']          ?? 'AMAN',
                'image_url'           => $d['image']            ?? null,
                'deteksi_yolo'        => $d['deteksi_yolo']     ?? null,
                'confidence_yolo'     => $d['confidence_yolo']  ?? null,
                'timestamp_iso'       => $updatedAt->toISOString(),
                'timestamp_formatted' => $updatedAt->format('d M Y, H:i'),
                'timestamp_time'      => $updatedAt->format('H:i'),
                'timestamp_date'      => $updatedAt->format('d M'),
                'updated_at'          => $d['updated_at']       ?? null,
                'sensor'              => $d,
            ]);
        }

        return response()->json([
            'success'             => false,
            'isOnline'            => false,
            'device'              => 'OFFLINE',
            'suhu'                => null,
            'kelembapan_udara'    => null,
            'kelembapan_tanah'    => null,
            'nilai_fuzzy'         => null,
            'status_hama'         => 'OFFLINE',
            'deteksi'             => 'OFFLINE',
            'image_url'           => null,
            'deteksi_yolo'        => null,
            'confidence_yolo'     => null,
            'timestamp_iso'       => null,
            'timestamp_formatted' => null,
            'timestamp_time'      => null,
            'timestamp_date'      => null,
            'updated_at'          => null,
            'sensor'              => null,
        ]);
    }

    // ================== ENDPOINT: /api/kamera/latest ==================
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

    private function buildRiwayatHtml(Collection $fotoData): string
    {
        if ($fotoData->isEmpty()) {
            return '<div class="foto-placeholder">📷 Belum ada foto dari kamera IoT.</div>';
        }
        $html = '<div class="foto-grid">';
        foreach ($fotoData as $fd) {
            $nilaiR = $this->resolveFuzzyValue($fd);
            [$statusR] = $this->getStatus($nilaiR);
            $badgeClass = $statusR === 'HAMA' ? 'chip-hama' : ($statusR === 'WASPADA' ? 'chip-waspada' : 'chip-aman');

            $yoloBadge = '';
            if ($fd->deteksi_yolo) {
                $confidence = round($fd->confidence_yolo * 100);
                $yoloBadge = '<span class="yolo-badge" style="position:absolute; top:4px; right:4px; background:' . ($confidence > 70 ? '#dc2626' : '#d97706') . '; color:white; font-size:9px; font-weight:700; padding:2px 6px; border-radius:4px; z-index:5;">🎯 ' . $fd->deteksi_yolo . ' (' . $confidence . '%)</span>';
            }

            $html .= '<a href="' . asset('storage/' . $fd->image) . '" target="_blank" class="foto-item" title="' . $fd->created_at->format('d M Y H:i') . '">
                <img src="' . asset('storage/' . $fd->image) . '" alt="Foto tanaman">
                ' . $yoloBadge . '
                <span class="foto-badge ' . $badgeClass . '">' . $statusR . '</span>
            </a>';
        }
        return $html . '</div>';
    }

    // ================== ENDPOINT: POST /api/sensor ==================
    public function store(Request $request)
    {
        $token = $request->header('X-API-TOKEN') ?? $request->input('api_token');
        if ($token !== env('IOT_API_TOKEN', 'smartfarm-secret-token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'suhu_udara'       => 'required|numeric|between:0,60',
            'kelembapan_udara' => 'required|numeric|between:0,100',
            'kelembapan_tanah' => 'required|numeric|between:0,100',
            'image'            => 'nullable|image|max:5120',
            'deteksi_yolo'     => 'nullable|string|max:255',
            'confidence_yolo'  => 'nullable|numeric|between:0,1',
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('kamera', 'public');
        }

        $nilaiFuzzy = $this->fuzzySugeno(
            $request->suhu_udara,
            $request->kelembapan_udara,
            $request->kelembapan_tanah
        );

        [$status] = $this->getHybridStatus(
            $request->deteksi_yolo,
            $request->confidence_yolo,
            $nilaiFuzzy
        );

        Cache::put('iot_live_data', [
            'suhu'             => $request->suhu_udara,
            'kelembapan_udara' => $request->kelembapan_udara,
            'kelembapan_tanah' => $request->kelembapan_tanah,
            'nilai_fuzzy'      => round($nilaiFuzzy, 4),
            'deteksi'          => $status,
            'deteksi_yolo'     => $request->deteksi_yolo,
            'confidence_yolo'  => $request->confidence_yolo,
            'image'            => $path ? asset('storage/' . $path) : null,
            'updated_at'       => now()->toIso8601String(),
        ], now()->addMinutes(7));

        $sensor = SensorReading::create([
            'suhu'             => $request->suhu_udara,
            'kelembapan_udara' => $request->kelembapan_udara,
            'kelembapan_tanah' => $request->kelembapan_tanah,
            'nilai_fuzzy'      => $nilaiFuzzy,
            'image'            => $path,
            'deteksi'          => $status,
            'deteksi_yolo'     => $request->deteksi_yolo,
            'confidence_yolo'  => $request->confidence_yolo,
        ]);

        if (in_array($status, ['HAMA', 'WASPADA'])) {
            $this->createNotification($status, $nilaiFuzzy, $sensor);
        }

        return response()->json([
            'message'            => 'Data diproses',
            'status'             => $status,
            'nilai_fuzzy'        => round($nilaiFuzzy, 4),
            'deteksi_yolo'       => $request->deteksi_yolo,
            'confidence_yolo'    => $request->confidence_yolo,
            'stored_in_cache'    => true,
            'stored_in_database' => true,
        ], 201);
    }

    // ================== MANUAL ==================
    public function manual()
    {
        if (Cache::has('iot_live_data')) {
            $cache = Cache::get('iot_live_data');
            $suhu  = $cache['suhu'] ?? 0;
            $udara = $cache['kelembapan_udara'] ?? 0;
            $tanah = $cache['kelembapan_tanah'] ?? 0;
        } else {
            $latest = SensorReading::latest()->first();
            if ($latest) {
                $suhu  = $latest->suhu;
                $udara = $latest->kelembapan_udara;
                $tanah = $latest->kelembapan_tanah;
            } else {
                return redirect()->route('dashboard')
                    ->with('error', '❌ Belum ada data sensor. Tunggu kiriman data dari IoT atau gunakan mode simulasi.');
            }
        }

        $nilaiFuzzy = $this->fuzzySugeno($suhu, $udara, $tanah);
        [$status] = $this->getStatus($nilaiFuzzy);

        $sensor = SensorReading::create([
            'suhu'             => $suhu,
            'kelembapan_udara' => $udara,
            'kelembapan_tanah' => $tanah,
            'nilai_fuzzy'      => $nilaiFuzzy,
            'deteksi'          => $status,
        ]);

        if (in_array($status, ['HAMA', 'WASPADA'])) {
            $this->createNotification($status, $nilaiFuzzy, $sensor);
        }

        return redirect()->route('dashboard')
            ->with('success', '✅ Data real-time berhasil disimpan ke database!');
    }

    // ==================================================================================
    // FUZZY SUGENO
    // ==================================================================================
    private function fuzzySugeno(float $suhu, float $udara, float $tanah): float
    {
        $sAman    = ThresholdSetting::getValue('suhu_aman',    22);
        $sWaspada = ThresholdSetting::getValue('suhu_waspada', 28);
        $sHama    = ThresholdSetting::getValue('suhu_hama',    32);

        $uAman    = ThresholdSetting::getValue('udara_aman',    60);
        $uWaspada = ThresholdSetting::getValue('udara_waspada', 75);
        $uHama    = ThresholdSetting::getValue('udara_hama',    85);

        $tAman    = ThresholdSetting::getValue('tanah_aman',    55);
        $tWaspada = ThresholdSetting::getValue('tanah_waspada', 68);
        $tHama    = ThresholdSetting::getValue('tanah_hama',    80);

        $dingin = max(0, min(1, ($sAman    - $suhu) / self::T_SUHU));
        $panas  = max(0, min(1, ($suhu  - $sWaspada) / max(0.01, $sHama - $sWaspada)));
        $hangat = max(0, min(1, 1 - $dingin - $panas));

        $kering_u = max(0, min(1, ($uAman    - $udara) / self::T_LEMBAP));
        $lembap_u = max(0, min(1, ($udara - $uWaspada) / max(0.01, $uHama - $uWaspada)));
        $normal_u = max(0, min(1, 1 - $kering_u - $lembap_u));

        $kering_t = max(0, min(1, ($tAman    - $tanah) / self::T_LEMBAP));
        $lembap_t = max(0, min(1, ($tanah - $tWaspada) / max(0.01, $tHama - $tWaspada)));
        $normal_t = max(0, min(1, 1 - $kering_t - $lembap_t));

        $rules = [
            [min($panas,  $lembap_u, $lembap_t), 1.00],
            [min($panas,  $lembap_u, $normal_t), 0.85],
            [min($panas,  $lembap_u, $kering_t), 0.75],
            [min($panas,  $normal_u, $lembap_t), 0.70],
            [min($panas,  $normal_u, $normal_t), 0.55],
            [min($panas,  $normal_u, $kering_t), 0.45],
            [min($panas,  $kering_u, $lembap_t), 0.50],
            [min($panas,  $kering_u, $normal_t), 0.40],
            [min($panas,  $kering_u, $kering_t), 0.30],
            [min($hangat, $lembap_u, $lembap_t), 0.80],
            [min($hangat, $lembap_u, $normal_t), 0.65],
            [min($hangat, $lembap_u, $kering_t), 0.55],
            [min($hangat, $normal_u, $lembap_t), 0.50],
            [min($hangat, $normal_u, $normal_t), 0.40],
            [min($hangat, $normal_u, $kering_t), 0.30],
            [min($hangat, $kering_u, $lembap_t), 0.35],
            [min($hangat, $kering_u, $normal_t), 0.25],
            [min($hangat, $kering_u, $kering_t), 0.20],
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

    private function getStatus(float $nilai): array
    {
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

    // ================== PREDIKSI ==================
    public function prediksi()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $isOnline    = Cache::has('iot_live_data');
        $latest      = SensorReading::latest()->first();
        $data        = SensorReading::latest()->take(10)->get()->reverse()->values();
        $fuzzyValues = $data->map(fn($d) => $this->resolveFuzzyValue($d))->values()->toArray();
        $nilai       = count($fuzzyValues) ? end($fuzzyValues) : 0;
        [$status, $class] = $this->getStatus($nilai);

        $diff = 0;
        if (count($fuzzyValues) > 1) {
            $totalDiff = 0;
            for ($i = 1; $i < count($fuzzyValues); $i++) {
                $totalDiff += ($fuzzyValues[$i] - $fuzzyValues[$i - 1]);
            }
            $diff = $totalDiff / (count($fuzzyValues) - 1);
        }

        $prediksi = $prediksiStatus = [];
        for ($i = 1; $i <= 3; $i++) {
            $next = max(0, min(1, $nilai + $diff * $i));
            $prediksi[] = round($next, 3);
            [$ps] = $this->getStatus($next);
            $prediksiStatus[] = $ps;
        }

        $labelsHistoris = $data->pluck('created_at')
            ->map(fn($d) => $d->format('H:i'))->values()->toArray();

        $thresholdHama    = ThresholdSetting::getValue('threshold_hama',    0.70);
        $thresholdWaspada = ThresholdSetting::getValue('threshold_waspada', 0.45);

        return view('prediksi', compact(
            'latest', 'nilai', 'status', 'class',
            'prediksi', 'prediksiStatus',
            'fuzzyValues', 'labelsHistoris', 'isOnline',
            'thresholdHama', 'thresholdWaspada'
        ));
    }

    // ================== RIWAYAT (USER) ==================
    public function riwayat()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $isOnline = Cache::has('iot_live_data');
        $query    = SensorReading::latest();

        $filter = request('filter');
        if ($filter === '7hari')      $query->where('created_at', '>=', now()->subDays(7));
        elseif ($filter === '1bulan') $query->where('created_at', '>=', now()->subMonth());
        elseif ($filter === '3bulan') $query->where('created_at', '>=', now()->subMonths(3));

        $filterDeteksi = request('deteksi');
        if (in_array($filterDeteksi, ['HAMA', 'WASPADA', 'AMAN'])) {
            $query->where('deteksi', $filterDeteksi);
        }

        $data = $query->paginate(10);

        // ✅ PERBAIKAN: Gunakan status yang sudah disimpan di database (hasil hybrid)
        $data->getCollection()->transform(function ($item) {
            $item->nilai   = round($item->nilai_fuzzy ?? 0, 3);
            $item->status  = $item->deteksi; // status sudah benar dari hybrid
            return $item;
        });

        return view('riwayat', compact('data', 'isOnline'));
    }

    // ================== KAMERA ==================
    public function kamera()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $isOnline = Cache::has('iot_live_data');

        if ($isOnline) {
            $cache  = Cache::get('iot_live_data');
            $nilai  = $cache['nilai_fuzzy'];
            [$status, $class] = $this->getStatus($nilai);

            $latest = SensorReading::whereNotNull('image')->latest()->first();

            if (!$latest) {
                $latest = new \stdClass();
                $latest->suhu             = $cache['suhu'];
                $latest->kelembapan_udara = $cache['kelembapan_udara'];
                $latest->kelembapan_tanah = $cache['kelembapan_tanah'];
                $latest->image            = null;
                $latest->created_at       = Carbon::parse($cache['updated_at'] ?? now());
                $latest->deteksi_yolo     = $cache['deteksi_yolo'] ?? null;
                $latest->confidence_yolo  = $cache['confidence_yolo'] ?? null;
            }
        } else {
            $latest = SensorReading::whereNotNull('image')->latest()->first();
            $nilai  = $latest ? $this->resolveFuzzyValue($latest) : 0;
            [$status, $class] = $this->getStatus($nilai);
        }

        return view('kamera', compact('latest', 'nilai', 'status', 'class', 'isOnline'));
    }

    // ==================================================================================
    // ADMIN
    // ==================================================================================

    public function adminDashboard()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $totalData = SensorReading::count();
        $totalHama = SensorReading::where('deteksi', 'HAMA')->count();
        $users     = User::orderBy('role')->orderBy('name')->get();
        $settings  = ThresholdSetting::all();

        return view('admin.dashboard', compact('totalData', 'totalHama', 'users', 'settings'));
    }

    public function updateThreshold(Request $request)
    {
        $request->validate([
            'settings.suhu_aman'     => 'required|numeric',
            'settings.suhu_waspada'  => 'required|numeric',
            'settings.suhu_hama'     => 'required|numeric',
            'settings.udara_aman'    => 'required|numeric|between:0,100',
            'settings.udara_waspada' => 'required|numeric|between:0,100',
            'settings.udara_hama'    => 'required|numeric|between:0,100',
            'settings.tanah_aman'    => 'required|numeric|between:0,100',
            'settings.tanah_waspada' => 'required|numeric|between:0,100',
            'settings.tanah_hama'    => 'required|numeric|between:0,100',
        ]);

        $s = $request->input('settings');

        $params = [
            'Suhu Udara'       => ['suhu_aman',  'suhu_waspada',  'suhu_hama'],
            'Kelembapan Udara' => ['udara_aman', 'udara_waspada', 'udara_hama'],
            'Kelembapan Tanah' => ['tanah_aman', 'tanah_waspada', 'tanah_hama'],
        ];

        foreach ($params as $label => [$kAman, $kWaspada, $kHama]) {
            $vAman    = (float) $s[$kAman];
            $vWaspada = (float) $s[$kWaspada];
            $vHama    = (float) $s[$kHama];

            if ($vAman >= $vWaspada) {
                return back()->withInput()
                    ->with('error', "❌ {$label}: Batas AMAN harus lebih kecil dari batas WASPADA.");
            }
            if ($vWaspada >= $vHama) {
                return back()->withInput()
                    ->with('error', "❌ {$label}: Batas WASPADA harus lebih kecil dari batas HAMA.");
            }
        }

        foreach ($s as $key => $value) {
            ThresholdSetting::where('key', $key)->update(['value' => (float) $value]);
        }

        ThresholdSetting::clearCache();

        return redirect()->route('admin.dashboard')
            ->with('success', '✅ Pengaturan kondisi ideal berhasil diperbarui.');
    }

    public function resetThreshold()
    {
        $defaults = [
            'suhu_aman'     => 22, 'suhu_waspada'  => 28, 'suhu_hama'     => 32,
            'udara_aman'    => 60, 'udara_waspada' => 75, 'udara_hama'    => 85,
            'tanah_aman'    => 55, 'tanah_waspada' => 68, 'tanah_hama'    => 80,
        ];

        foreach ($defaults as $key => $value) {
            ThresholdSetting::where('key', $key)->update(['value' => $value]);
        }

        ThresholdSetting::clearCache();

        return redirect()->route('admin.dashboard')
            ->with('success', '🔄 Pengaturan dikembalikan ke nilai default penelitian.');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:user,admin',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => $request->role,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', '✅ Pengguna baru berhasil ditambahkan.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', '🗑️ Pengguna berhasil dihapus.');
    }

    public function debugFuzzy(Request $request)
    {
        $suhu  = (float) ($request->input('suhu',  28));
        $udara = (float) ($request->input('udara', 70));
        $tanah = (float) ($request->input('tanah', 60));

        $sAman    = ThresholdSetting::getValue('suhu_aman',    22);
        $sWaspada = ThresholdSetting::getValue('suhu_waspada', 28);
        $sHama    = ThresholdSetting::getValue('suhu_hama',    32);
        $uAman    = ThresholdSetting::getValue('udara_aman',    60);
        $uWaspada = ThresholdSetting::getValue('udara_waspada', 75);
        $uHama    = ThresholdSetting::getValue('udara_hama',    85);
        $tAman    = ThresholdSetting::getValue('tanah_aman',    55);
        $tWaspada = ThresholdSetting::getValue('tanah_waspada', 68);
        $tHama    = ThresholdSetting::getValue('tanah_hama',    80);

        $dingin   = max(0, min(1, ($sAman    - $suhu)  / self::T_SUHU));
        $panas    = max(0, min(1, ($suhu  - $sWaspada) / max(0.01, $sHama - $sWaspada)));
        $hangat   = max(0, min(1, 1 - $dingin - $panas));

        $kering_u = max(0, min(1, ($uAman    - $udara) / self::T_LEMBAP));
        $lembap_u = max(0, min(1, ($udara - $uWaspada) / max(0.01, $uHama - $uWaspada)));
        $normal_u = max(0, min(1, 1 - $kering_u - $lembap_u));

        $kering_t = max(0, min(1, ($tAman    - $tanah) / self::T_LEMBAP));
        $lembap_t = max(0, min(1, ($tanah - $tWaspada) / max(0.01, $tHama - $tWaspada)));
        $normal_t = max(0, min(1, 1 - $kering_t - $lembap_t));

        $rulesDef = [
            ['label'=>'panas+lembap_u+lembap_t', 'alpha'=>min($panas,$lembap_u,$lembap_t), 'z'=>1.00],
            ['label'=>'panas+lembap_u+normal_t', 'alpha'=>min($panas,$lembap_u,$normal_t), 'z'=>0.85],
            ['label'=>'panas+lembap_u+kering_t', 'alpha'=>min($panas,$lembap_u,$kering_t), 'z'=>0.75],
            ['label'=>'panas+normal_u+lembap_t', 'alpha'=>min($panas,$normal_u,$lembap_t), 'z'=>0.70],
            ['label'=>'panas+normal_u+normal_t', 'alpha'=>min($panas,$normal_u,$normal_t), 'z'=>0.55],
            ['label'=>'panas+normal_u+kering_t', 'alpha'=>min($panas,$normal_u,$kering_t), 'z'=>0.45],
            ['label'=>'panas+kering_u+lembap_t', 'alpha'=>min($panas,$kering_u,$lembap_t), 'z'=>0.50],
            ['label'=>'panas+kering_u+normal_t', 'alpha'=>min($panas,$kering_u,$normal_t), 'z'=>0.40],
            ['label'=>'panas+kering_u+kering_t', 'alpha'=>min($panas,$kering_u,$kering_t), 'z'=>0.30],
            ['label'=>'hangat+lembap_u+lembap_t','alpha'=>min($hangat,$lembap_u,$lembap_t),'z'=>0.80],
            ['label'=>'hangat+lembap_u+normal_t','alpha'=>min($hangat,$lembap_u,$normal_t),'z'=>0.65],
            ['label'=>'hangat+lembap_u+kering_t','alpha'=>min($hangat,$lembap_u,$kering_t),'z'=>0.55],
            ['label'=>'hangat+normal_u+lembap_t','alpha'=>min($hangat,$normal_u,$lembap_t),'z'=>0.50],
            ['label'=>'hangat+normal_u+normal_t','alpha'=>min($hangat,$normal_u,$normal_t),'z'=>0.40],
            ['label'=>'hangat+normal_u+kering_t','alpha'=>min($hangat,$normal_u,$kering_t),'z'=>0.30],
            ['label'=>'hangat+kering_u+lembap_t','alpha'=>min($hangat,$kering_u,$lembap_t),'z'=>0.35],
            ['label'=>'hangat+kering_u+normal_t','alpha'=>min($hangat,$kering_u,$normal_t),'z'=>0.25],
            ['label'=>'hangat+kering_u+kering_t','alpha'=>min($hangat,$kering_u,$kering_t),'z'=>0.20],
            ['label'=>'dingin+lembap_u+lembap_t','alpha'=>min($dingin,$lembap_u,$lembap_t),'z'=>0.45],
            ['label'=>'dingin+lembap_u+normal_t','alpha'=>min($dingin,$lembap_u,$normal_t),'z'=>0.35],
            ['label'=>'dingin+lembap_u+kering_t','alpha'=>min($dingin,$lembap_u,$kering_t),'z'=>0.25],
            ['label'=>'dingin+normal_u+lembap_t','alpha'=>min($dingin,$normal_u,$lembap_t),'z'=>0.30],
            ['label'=>'dingin+normal_u+normal_t','alpha'=>min($dingin,$normal_u,$normal_t),'z'=>0.20],
            ['label'=>'dingin+normal_u+kering_t','alpha'=>min($dingin,$normal_u,$kering_t),'z'=>0.15],
            ['label'=>'dingin+kering_u+lembap_t','alpha'=>min($dingin,$kering_u,$lembap_t),'z'=>0.20],
            ['label'=>'dingin+kering_u+normal_t','alpha'=>min($dingin,$kering_u,$normal_t),'z'=>0.15],
            ['label'=>'dingin+kering_u+kering_t','alpha'=>min($dingin,$kering_u,$kering_t),'z'=>0.10],
        ];

        $num = $den = 0;
        $activeRules = [];
        foreach ($rulesDef as $rule) {
            $num += $rule['alpha'] * $rule['z'];
            $den += $rule['alpha'];
            if ($rule['alpha'] > 0) {
                $activeRules[] = [
                    'rule'  => $rule['label'],
                    'alpha' => round($rule['alpha'], 4),
                    'z'     => $rule['z'],
                    'kontribusi' => round($rule['alpha'] * $rule['z'], 4),
                ];
            }
        }

        $nilaiFuzzy = $den > 0 ? $num / $den : 0;
        [$status] = $this->getStatus($nilaiFuzzy);

        return response()->json([
            'input' => [
                'suhu'  => $suhu,
                'udara' => $udara,
                'tanah' => $tanah,
            ],
            'thresholds' => [
                'suhu_aman'       => $sAman,    'suhu_waspada'  => $sWaspada, 'suhu_hama'  => $sHama,
                'udara_aman'      => $uAman,    'udara_waspada' => $uWaspada, 'udara_hama' => $uHama,
                'tanah_aman'      => $tAman,    'tanah_waspada' => $tWaspada, 'tanah_hama' => $tHama,
                'threshold_hama'    => ThresholdSetting::getValue('threshold_hama',    0.70),
                'threshold_waspada' => ThresholdSetting::getValue('threshold_waspada', 0.45),
            ],
            'membership' => [
                'suhu'  => ['dingin'=>round($dingin,4), 'hangat'=>round($hangat,4), 'panas'=>round($panas,4)],
                'udara' => ['kering'=>round($kering_u,4), 'normal'=>round($normal_u,4), 'lembap'=>round($lembap_u,4)],
                'tanah' => ['kering'=>round($kering_t,4), 'normal'=>round($normal_t,4), 'lembap'=>round($lembap_t,4)],
            ],
            'rules' => [
                'total'       => count($rulesDef),
                'aktif'       => count($activeRules),
                'detail_aktif'=> $activeRules,
                'numerator'   => round($num, 6),
                'denominator' => round($den, 6),
            ],
            'output' => [
                'nilai_fuzzy' => round($nilaiFuzzy, 4),
                'status'      => $status,
            ],
        ], 200, [], JSON_PRETTY_PRINT);
    }

    // ================== ADMIN: RIWAYAT DATA ==================
    public function adminRiwayat()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $query = SensorReading::latest();

        $filter = request('filter');
        if ($filter === '7hari')      $query->where('created_at', '>=', now()->subDays(7));
        elseif ($filter === '1bulan') $query->where('created_at', '>=', now()->subMonth());
        elseif ($filter === '3bulan') $query->where('created_at', '>=', now()->subMonths(3));

        $filterDeteksi = request('deteksi');
        if (in_array($filterDeteksi, ['HAMA', 'WASPADA', 'AMAN'])) {
            $query->where('deteksi', $filterDeteksi);
        }

        $data = $query->paginate(15);

        $data->getCollection()->transform(function ($item) {
            $nilai = $this->resolveFuzzyValue($item);
            [$status] = $this->getStatus($nilai);
            $item->nilai = round($nilai, 3);
            $item->status = $status;
            return $item;
        });

        return view('admin.riwayat', compact('data'));
    }

    // ================== ADMIN: HAPUS DATA RIWAYAT ==================
    public function adminDestroyRiwayat($id)
    {
        $id = (int) $id;
        Log::info('=== HAPUS DATA ID: ' . $id . ' ===');

        try {
            $data = SensorReading::findOrFail($id);

            if ($data->image && Storage::disk('public')->exists($data->image)) {
                Storage::disk('public')->delete($data->image);
            }

            $data->delete();

            return redirect()->route('admin.riwayat')
                ->with('success', '🗑️ Data riwayat berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal hapus data ID ' . $id . ': ' . $e->getMessage());
            return redirect()->route('admin.riwayat')
                ->with('error', '❌ Gagal menghapus data: ' . $e->getMessage());
        }
    }

    // ================== ADMIN: HAPUS SEMUA DATA RIWAYAT ==================
    public function adminDestroyAllRiwayat()
    {
        Log::info('=== HAPUS SEMUA DATA ===');

        try {
            $items = SensorReading::whereNotNull('image')->get();

            foreach ($items as $item) {
                if ($item->image && Storage::disk('public')->exists($item->image)) {
                    Storage::disk('public')->delete($item->image);
                }
            }

            SensorReading::query()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Semua data berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal hapus semua data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}