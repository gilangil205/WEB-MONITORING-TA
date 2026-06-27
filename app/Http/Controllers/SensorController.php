<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorReading;
use App\Models\ThresholdSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SensorController extends Controller
{
    // ── Lebar transisi fuzzy (°C / %) — TIDAK diatur admin ─────────────────
    // Menentukan seberapa "landai" perubahan derajat keanggotaan
    // di sekitar batas zona yang diatur admin.
    private const T_SUHU   = 3;   // °C  — transisi antar zona suhu
    private const T_LEMBAP = 5;   // %   — transisi antar zona kelembapan

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
            'success' => false, 'isOnline' => false, 'device' => 'OFFLINE',
            'suhu' => null, 'kelembapan_udara' => null, 'kelembapan_tanah' => null,
            'nilai_fuzzy' => null, 'status_hama' => 'OFFLINE', 'deteksi' => 'OFFLINE',
            'image_url' => null, 'timestamp_iso' => null, 'timestamp_formatted' => null,
            'timestamp_time' => null, 'timestamp_date' => null, 'updated_at' => null,
            'sensor' => null,
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

        // ✅ SIMPAN KE DATABASE SETIAP KALI (tanpa cooldown 15 menit)
        SensorReading::create([
            'suhu'             => $request->suhu_udara,
            'kelembapan_udara' => $request->kelembapan_udara,
            'kelembapan_tanah' => $request->kelembapan_tanah,
            'nilai_fuzzy'      => $nilai,
            'image'            => $path,
            'deteksi'          => $status,
        ]);

        return response()->json([
            'message'            => 'Data diproses',
            'status'             => $status,
            'nilai'              => round($nilai, 4),
            'stored_in_cache'    => true,
            'stored_in_database' => true, // selalu true karena disimpan setiap kiriman
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
    // FUZZY SUGENO — 4 Tahap (3-zona per parameter)
    //
    // DESAIN BARU: admin mengatur 3 batas per parameter:
    //   suhu_aman    → suhu ≤ ini masuk zona dingin (aman)
    //   suhu_waspada → suhu di antara aman–waspada
    //   suhu_hama    → suhu ≥ ini masuk zona panas (hama)
    //   (sama untuk udara_ dan tanah_)
    //
    // Fungsi membership 3 zona:
    //   rendah (dingin/kering) = clamp((X_aman    - X) / T, 0, 1)
    //   tinggi (panas/lembap)  = clamp((X          - X_waspada) / (X_hama - X_waspada), 0, 1)
    //   normal (hangat/normal) = clamp(1 - rendah - tinggi, 0, 1)
    //
    // TAHAP 2 — 27 rules sama seperti sebelumnya
    // TAHAP 3 — Defuzzifikasi Sugeno (Weighted Average)
    // TAHAP 4 — getStatus() pakai threshold_hama/waspada (fixed di DB, tidak diedit UI)
    // ==================================================================================
    private function fuzzySugeno(float $suhu, float $udara, float $tanah): float
    {
        // ── Baca 9 nilai dari DB (via cache 1 jam) ────────────────────────────────
        $sAman    = ThresholdSetting::getValue('suhu_aman',    22);
        $sWaspada = ThresholdSetting::getValue('suhu_waspada', 28);
        $sHama    = ThresholdSetting::getValue('suhu_hama',    32);

        $uAman    = ThresholdSetting::getValue('udara_aman',    60);
        $uWaspada = ThresholdSetting::getValue('udara_waspada', 75);
        $uHama    = ThresholdSetting::getValue('udara_hama',    85);

        $tAman    = ThresholdSetting::getValue('tanah_aman',    55);
        $tWaspada = ThresholdSetting::getValue('tanah_waspada', 68);
        $tHama    = ThresholdSetting::getValue('tanah_hama',    80);

        // ── TAHAP 1A: Membership SUHU ─────────────────────────────────────────────
        //   dingin: X ≤ sAman         → 1 (aman), turun ke 0 saat mendekati sWaspada
        //   panas:  X ≥ sHama         → 1 (hama), naik dari 0 mulai sWaspada
        //   hangat: antara keduanya   → zona waspada
        $dingin = max(0, min(1, ($sAman    - $suhu) / self::T_SUHU));
        $panas  = max(0, min(1, ($suhu  - $sWaspada) / max(0.01, $sHama - $sWaspada)));
        $hangat = max(0, min(1, 1 - $dingin - $panas));

        // ── TAHAP 1B: Membership KELEMBAPAN UDARA ─────────────────────────────────
        $kering_u = max(0, min(1, ($uAman    - $udara) / self::T_LEMBAP));
        $lembap_u = max(0, min(1, ($udara - $uWaspada) / max(0.01, $uHama - $uWaspada)));
        $normal_u = max(0, min(1, 1 - $kering_u - $lembap_u));

        // ── TAHAP 1C: Membership KELEMBAPAN TANAH ─────────────────────────────────
        $kering_t = max(0, min(1, ($tAman    - $tanah) / self::T_LEMBAP));
        $lembap_t = max(0, min(1, ($tanah - $tWaspada) / max(0.01, $tHama - $tWaspada)));
        $normal_t = max(0, min(1, 1 - $kering_t - $lembap_t));

        // ── TAHAP 2: EVALUASI 27 RULES ────────────────────────────────────────────
        $rules = [
            // Suhu PANAS (risiko tinggi)
            [min($panas,  $lembap_u, $lembap_t), 1.00],
            [min($panas,  $lembap_u, $normal_t), 0.85],
            [min($panas,  $lembap_u, $kering_t), 0.75],
            [min($panas,  $normal_u, $lembap_t), 0.70],
            [min($panas,  $normal_u, $normal_t), 0.55],
            [min($panas,  $normal_u, $kering_t), 0.45],
            [min($panas,  $kering_u, $lembap_t), 0.50],
            [min($panas,  $kering_u, $normal_t), 0.40],
            [min($panas,  $kering_u, $kering_t), 0.30],
            // Suhu HANGAT (risiko sedang)
            [min($hangat, $lembap_u, $lembap_t), 0.80],
            [min($hangat, $lembap_u, $normal_t), 0.65],
            [min($hangat, $lembap_u, $kering_t), 0.55],
            [min($hangat, $normal_u, $lembap_t), 0.50],
            [min($hangat, $normal_u, $normal_t), 0.40],
            [min($hangat, $normal_u, $kering_t), 0.30],
            [min($hangat, $kering_u, $lembap_t), 0.35],
            [min($hangat, $kering_u, $normal_t), 0.25],
            [min($hangat, $kering_u, $kering_t), 0.20],
            // Suhu DINGIN (risiko rendah)
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

        // ── TAHAP 3: DEFUZZIFIKASI SUGENO (Weighted Average) ──────────────────────
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

    // ── TAHAP 4: KEPUTUSAN ────────────────────────────────────────────────────────
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

        // Validasi urutan: aman < waspada < hama
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
}