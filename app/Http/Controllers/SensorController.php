<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorReading;
use App\Models\ThresholdSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SensorController extends Controller
{
    // ================== HELPER: RESOLVE NILAI FUZZY ==================
    private function resolveFuzzyValue($item)
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

    // ==================================================================================
    // ENDPOINT: /live-data
    // ==================================================================================
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
            'timestamp_iso'       => null,
            'timestamp_formatted' => null,
            'timestamp_time'      => null,
            'timestamp_date'      => null,
            'updated_at'          => null,
            'sensor'              => null,
        ]);
    }

    // ==================================================================================
    // ENDPOINT: /api/kamera/latest
    // ==================================================================================
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

    private function buildRiwayatHtml($fotoData): string
    {
        if ($fotoData->isEmpty()) {
            return '<div class="foto-placeholder">📷 Belum ada foto dari kamera IoT.</div>';
        }
        $html = '<div class="foto-grid">';
        foreach ($fotoData as $fd) {
            $nilaiR = $this->resolveFuzzyValue($fd);
            [$statusR] = $this->getStatus($nilaiR);
            $badgeClass = $statusR === 'HAMA' ? 'chip-hama' : ($statusR === 'WASPADA' ? 'chip-waspada' : 'chip-aman');
            $html .= '<a href="' . asset('storage/' . $fd->image) . '" target="_blank" class="foto-item" title="' . $fd->created_at->format('d M Y H:i') . '">
                <img src="' . asset('storage/' . $fd->image) . '" alt="Foto tanaman">
                <span class="foto-badge ' . $badgeClass . '">' . $statusR . '</span>
            </a>';
        }
        return $html . '</div>';
    }

    // ==================================================================================
    // ENDPOINT: POST /api/sensor — terima data dari IoT (ESP32)
    // ==================================================================================
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
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('kamera', 'public');
        }

        $nilai = $this->fuzzySugeno(
            $request->suhu_udara,
            $request->kelembapan_udara,
            $request->kelembapan_tanah
        );
        [$status] = $this->getStatus($nilai);

        Cache::put('iot_live_data', [
            'suhu'             => $request->suhu_udara,
            'kelembapan_udara' => $request->kelembapan_udara,
            'kelembapan_tanah' => $request->kelembapan_tanah,
            'nilai_fuzzy'      => round($nilai, 4),
            'deteksi'          => $status,
            'image'            => $path ? asset('storage/' . $path) : null,
            'updated_at'       => now()->toIso8601String(),
        ], now()->addMinutes(7));

        $dbSaved = false;
        if (!Cache::has('iot_db_cooldown')) {
            $now         = Carbon::now();
            $minute      = floor($now->minute / 15) * 15;
            $timeRounded = $now->copy()->setMinute($minute)->setSecond(0);

            SensorReading::create([
                'suhu'             => $request->suhu_udara,
                'kelembapan_udara' => $request->kelembapan_udara,
                'kelembapan_tanah' => $request->kelembapan_tanah,
                'nilai_fuzzy'      => $nilai,
                'image'            => $path,
                'deteksi'          => $status,
                'created_at'       => $timeRounded,
            ]);

            Cache::put('iot_db_cooldown', true, now()->addSeconds(890));
            $dbSaved = true;
        }

        return response()->json([
            'message'            => 'Data diproses',
            'status'             => $status,
            'nilai'              => round($nilai, 4),
            'stored_in_cache'    => true,
            'stored_in_database' => $dbSaved,
        ], 201);
    }

    // ================== MANUAL ==================
    public function manual()
    {
        $suhu  = rand(22, 36);
        $udara = rand(55, 95);
        $tanah = rand(35, 90);
        $nilai = $this->fuzzySugeno($suhu, $udara, $tanah);
        [$status] = $this->getStatus($nilai);

        SensorReading::create([
            'suhu'             => $suhu,
            'kelembapan_udara' => $udara,
            'kelembapan_tanah' => $tanah,
            'nilai_fuzzy'      => $nilai,
            'deteksi'          => $status,
        ]);

        return redirect()->route('dashboard')->with('success', 'Data manual berhasil ditambahkan');
    }

    // ==================================================================================
    // FUZZY SUGENO — 4 Tahap
    //
    // TAHAP 1 — FUZZIFIKASI:
    //   Mengubah nilai sensor crisp → derajat keanggotaan (0.0–1.0).
    //   Suhu       → dibaca dari ThresholdSetting DB (bisa diubah admin)
    //   Udara/Tanah→ hardcode (tidak diubah admin pada versi ini)
    //
    // TAHAP 2 — EVALUASI RULES:
    //   27 rules IF-THEN, firing_strength = min(derajat_anteseden).
    //   Output setiap rule adalah konstanta z (ciri khas Fuzzy Sugeno).
    //
    // TAHAP 3 — DEFUZZIFIKASI (Weighted Average / Sugeno):
    //   nilai = Σ(firing_strength × z) / Σ(firing_strength)
    //   Menghasilkan satu angka crisp 0.0–1.0.
    //
    // TAHAP 4 — KEPUTUSAN:
    //   getStatus() membandingkan nilai dengan threshold dari DB.
    // ==================================================================================
    private function fuzzySugeno(float $suhu, float $udara, float $tanah): float
    {
        // ── Baca threshold suhu dari DB (via cache 1 jam) ──────────────────────────
        $spMin = ThresholdSetting::getValue('suhu_panas_min',  30);  // default 30°C
        $sdMax = ThresholdSetting::getValue('suhu_dingin_max', 25);  // default 25°C
        $shMin = ThresholdSetting::getValue('suhu_hangat_min', 22);  // default 22°C
        $shMax = ThresholdSetting::getValue('suhu_hangat_max', 32);  // default 32°C

        // ── TAHAP 1A: Fungsi membership SUHU (trapesoid/segitiga) ─────────────────
        //
        //   $dingin → tinggi saat suhu rendah, turun linier ke 0 saat mendekati $shMin
        //   $hangat → naik dari $shMin, puncak di tengah, turun ke 0 di $shMax
        //   $panas  → 0 di bawah $spMin, naik linier ke 1 seiring suhu naik
        //
        //   PERBAIKAN BUG: pembagi $panas semula memakai / $spMin (30) — salah.
        //   Seharusnya / 5 (lebar transisi tetap 5°C seperti desain awal),
        //   konsisten dengan $dingin dan $hangat yang juga menggunakan lebar 5°C.
        //
        $dingin = max(0, min(1, ($sdMax - $suhu)  / max(0.01, $sdMax - $shMin)));
        $hangat = max(0, min(1, min(
            ($suhu  - $shMin) / max(0.01, $shMax - $shMin),
            ($shMax - $suhu)  / max(0.01, $shMax - $spMin)
        )));
        // ✅ DIPERBAIKI: pembagi 5 (lebar transisi tetap), bukan $spMin
        $panas  = max(0, min(1, ($suhu - $spMin) / 5));

        // ── TAHAP 1B: Fungsi membership KELEMBAPAN UDARA ──────────────────────────
        //   Zona kering  : udara < 65%  → membership tinggi saat udara makin rendah
        //   Zona normal  : 60–85%       → membership segitiga, puncak di tengah
        //   Zona lembap  : udara > 78%  → membership tinggi saat udara makin tinggi
        //
        $kering_u = max(0, min(1, (65 - $udara) / 15));
        $normal_u = max(0, min(1, min(($udara - 60) / 12, (85 - $udara) / 13)));
        $lembap_u = max(0, min(1, ($udara - 78) / 12));

        // ── TAHAP 1C: Fungsi membership KELEMBAPAN TANAH ──────────────────────────
        //   Zona kering  : tanah < 50%  → membership tinggi saat tanah makin kering
        //   Zona normal  : 40–80%       → membership segitiga, puncak di tengah
        //   Zona lembap  : tanah > 65%  → membership tinggi saat tanah makin basah
        //
        $kering_t = max(0, min(1, (50 - $tanah) / 20));
        $normal_t = max(0, min(1, min(($tanah - 40) / 20, (80 - $tanah) / 20)));
        $lembap_t = max(0, min(1, ($tanah - 65) / 20));

        // ── TAHAP 2: EVALUASI 27 RULES ────────────────────────────────────────────
        //   Format: [firing_strength, z_output]
        //   firing_strength = min(derajat_anteseden) — operator AND Fuzzy
        //   z_output        = konstanta output (ciri khas Sugeno, bukan himpunan)
        //
        //   Urutan: panas (9 rules) → hangat (9 rules) → dingin (9 rules)
        //   Setiap kelompok: kombinasi lembap/normal/kering untuk udara × tanah
        //
        $rules = [
            // ── Suhu PANAS ──────────────────────────────────────────────────────
            [min($panas,  $lembap_u, $lembap_t), 1.00],  // panas + lembap + lembap → max risiko
            [min($panas,  $lembap_u, $normal_t), 0.85],
            [min($panas,  $lembap_u, $kering_t), 0.75],
            [min($panas,  $normal_u, $lembap_t), 0.70],
            [min($panas,  $normal_u, $normal_t), 0.55],
            [min($panas,  $normal_u, $kering_t), 0.45],
            [min($panas,  $kering_u, $lembap_t), 0.50],
            [min($panas,  $kering_u, $normal_t), 0.40],
            [min($panas,  $kering_u, $kering_t), 0.30],  // panas + kering + kering → masih risiko
            // ── Suhu HANGAT ─────────────────────────────────────────────────────
            [min($hangat, $lembap_u, $lembap_t), 0.80],
            [min($hangat, $lembap_u, $normal_t), 0.65],
            [min($hangat, $lembap_u, $kering_t), 0.55],
            [min($hangat, $normal_u, $lembap_t), 0.50],
            [min($hangat, $normal_u, $normal_t), 0.40],
            [min($hangat, $normal_u, $kering_t), 0.30],
            [min($hangat, $kering_u, $lembap_t), 0.35],
            [min($hangat, $kering_u, $normal_t), 0.25],
            [min($hangat, $kering_u, $kering_t), 0.20],
            // ── Suhu DINGIN ─────────────────────────────────────────────────────
            [min($dingin, $lembap_u, $lembap_t), 0.45],
            [min($dingin, $lembap_u, $normal_t), 0.35],
            [min($dingin, $lembap_u, $kering_t), 0.25],
            [min($dingin, $normal_u, $lembap_t), 0.30],
            [min($dingin, $normal_u, $normal_t), 0.20],
            [min($dingin, $normal_u, $kering_t), 0.15],
            [min($dingin, $kering_u, $lembap_t), 0.20],
            [min($dingin, $kering_u, $normal_t), 0.15],
            [min($dingin, $kering_u, $kering_t), 0.10],  // dingin + kering + kering → min risiko
        ];

        // ── TAHAP 3: DEFUZZIFIKASI SUGENO (Weighted Average) ──────────────────────
        //   nilai = Σ(firing_strength × z) / Σ(firing_strength)
        //   Ini adalah metode defuzzifikasi khas Sugeno — menghasilkan nilai crisp
        //   langsung tanpa perlu menghitung luas area seperti metode Mamdani.
        //
        $num = $den = 0;
        foreach ($rules as [$r, $z]) {
            $num += $r * $z;
            $den += $r;
        }

        if ($den == 0) {
            Log::warning("Fuzzy: semua rules firing_strength = 0 — Suhu:{$suhu} Udara:{$udara} Tanah:{$tanah}");
            return 0;
        }

        return $num / $den; // nilai crisp 0.0–1.0
    }

    // ── TAHAP 4: KEPUTUSAN ────────────────────────────────────────────────────────
    // Membandingkan output defuzzifikasi dengan threshold dari DB.
    // threshold_hama dan threshold_waspada bisa diubah admin dari halaman admin.
    // ─────────────────────────────────────────────────────────────────────────────
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

        if ($isOnline) {
            $cache = Cache::get('iot_live_data');
            $nilai = $cache['nilai_fuzzy'] ?? $nilai;
            [$status, $class] = $this->getStatus($nilai);
        }

        $labels     = $data->pluck('created_at')->map(fn($d) => $d->format('H:i'))->values();
        $suhu       = $data->pluck('suhu')->values();
        $suhu_udara = $suhu;
        $udara      = $data->pluck('kelembapan_udara')->values();
        $tanah      = $data->pluck('kelembapan_tanah')->values();
        $fuzzyChart = $data->map(fn($d) => round($this->resolveFuzzyValue($d), 3))->values();

        return view('dashboard', compact(
            'latest', 'data', 'nilai', 'status', 'class',
            'labels', 'suhu', 'suhu_udara', 'udara', 'tanah', 'fuzzyChart', 'isOnline'
        ));
    }

    public function monitoring()
    {
        return redirect()->route('dashboard');
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

        return view('prediksi', compact(
            'latest', 'nilai', 'status', 'class',
            'prediksi', 'prediksiStatus',
            'fuzzyValues', 'labelsHistoris', 'isOnline'
        ));
    }

    // ================== RIWAYAT ==================
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
        $data->getCollection()->transform(function ($item) {
            $nilai = $this->resolveFuzzyValue($item);
            [$status] = $this->getStatus($nilai);
            $item->nilai   = round($nilai, 3);
            $item->status  = $status;
            $item->deteksi = $status;
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

            $latest = new \stdClass();
            $latest->suhu             = $cache['suhu'];
            $latest->kelembapan_udara = $cache['kelembapan_udara'];
            $latest->kelembapan_tanah = $cache['kelembapan_tanah'];
            $latest->image            = null;
            $latest->created_at       = Carbon::parse($cache['updated_at'] ?? now());
        } else {
            $latest = SensorReading::latest()->first();
            $nilai  = $this->resolveFuzzyValue($latest);
            [$status, $class] = $this->getStatus($nilai);
        }

        return view('kamera', compact('latest', 'nilai', 'status', 'class', 'isOnline'));
    }

    // ── Method admin (updateThreshold, resetThreshold, storeUser, deleteUser,
    //    adminDashboard) DIHAPUS dari sini.
    //    Semua sudah dipindah ke AdminController.php yang merupakan tempatnya.
    //    Route /admin/* di web.php sudah mengarah ke AdminController, bukan ke sini.
}