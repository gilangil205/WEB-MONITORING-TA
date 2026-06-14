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
    // ── Lebar transisi fuzzy — konstanta, TIDAK diatur admin ────────────────
    // Menentukan seberapa landai perubahan dari kering→normal→lembap dan
    // dingin→hangat→panas di sekitar nilai min/max yang diatur admin.
    private const T_SUHU   = 5;   // °C
    private const T_LEMBAP = 10;  // %

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
    // Membership suhu/udara/tanah diturunkan dari 6 nilai yang diatur admin:
    // suhu_min, suhu_max, udara_min, udara_max, tanah_min, tanah_max.
    // Lebar transisi (T_SUHU / T_LEMBAP) tetap konstan di kode.
    //
    // Untuk parameter X dengan batas ideal [min, max] dan lebar transisi T:
    //   rendah(X) = clamp((min - X) / T, 0, 1)   → 1 jika X jauh di bawah min
    //   tinggi(X) = clamp((X - max) / T, 0, 1)   → 1 jika X jauh di atas max
    //   normal(X) = clamp(1 - rendah(X) - tinggi(X), 0, 1)
    //
    // TAHAP 2 — 27 rules (firing_strength = min(anteseden), z = konstanta)
    // TAHAP 3 — Defuzzifikasi Sugeno (Weighted Average)
    // TAHAP 4 — getStatus() pakai threshold_hama / threshold_waspada (fixed di DB)
    // ==================================================================================
    private function fuzzySugeno(float $suhu, float $udara, float $tanah): float
    {
        // ── Baca 6 nilai admin dari DB (via cache 1 jam) ──────────────────────────
        $sMin = ThresholdSetting::getValue('suhu_min',  22);
        $sMax = ThresholdSetting::getValue('suhu_max',  30);
        $uMin = ThresholdSetting::getValue('udara_min', 60);
        $uMax = ThresholdSetting::getValue('udara_max', 80);
        $tMin = ThresholdSetting::getValue('tanah_min', 55);
        $tMax = ThresholdSetting::getValue('tanah_max', 75);

        // ── TAHAP 1A: Fungsi membership SUHU ──────────────────────────────────────
        $dingin = max(0, min(1, ($sMin - $suhu) / self::T_SUHU));
        $panas  = max(0, min(1, ($suhu - $sMax) / self::T_SUHU));
        $hangat = max(0, min(1, 1 - $dingin - $panas));

        // ── TAHAP 1B: Fungsi membership KELEMBAPAN UDARA ──────────────────────────
        $kering_u = max(0, min(1, ($uMin - $udara) / self::T_LEMBAP));
        $lembap_u = max(0, min(1, ($udara - $uMax) / self::T_LEMBAP));
        $normal_u = max(0, min(1, 1 - $kering_u - $lembap_u));

        // ── TAHAP 1C: Fungsi membership KELEMBAPAN TANAH ──────────────────────────
        $kering_t = max(0, min(1, ($tMin - $tanah) / self::T_LEMBAP));
        $lembap_t = max(0, min(1, ($tanah - $tMax) / self::T_LEMBAP));
        $normal_t = max(0, min(1, 1 - $kering_t - $lembap_t));

        // ── TAHAP 2: EVALUASI 27 RULES ────────────────────────────────────────────
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

        // ── TAHAP 3: DEFUZZIFIKASI SUGENO (Weighted Average) ──────────────────────
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

    // ── TAHAP 4: KEPUTUSAN ───────────────────────────────────────────
    // threshold_hama (0.70) & threshold_waspada (0.45) tetap di DB sebagai
    // nilai fixed (tidak ditampilkan/diedit di UI admin).
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
    // =============================== AREA ADMIN =======================================
    //  Admin hanya mengatur 6 nilai: suhu_min/max, udara_min/max, tanah_min/max,
    //  serta mengelola user (tambah & hapus).
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

    // ── Simpan 6 nilai min/max (suhu, udara, tanah) ───────────────────────
    public function updateThreshold(Request $request)
    {
        $request->validate([
            'settings.suhu_min'  => 'required|numeric',
            'settings.suhu_max'  => 'required|numeric',
            'settings.udara_min' => 'required|numeric|between:0,100',
            'settings.udara_max' => 'required|numeric|between:0,100',
            'settings.tanah_min' => 'required|numeric|between:0,100',
            'settings.tanah_max' => 'required|numeric|between:0,100',
        ]);

        $s = $request->input('settings');

        // Validasi logis: setiap min harus < max
        $pairs = [
            'Suhu Udara'       => ['suhu_min',  'suhu_max'],
            'Kelembapan Udara' => ['udara_min', 'udara_max'],
            'Kelembapan Tanah' => ['tanah_min', 'tanah_max'],
        ];
        foreach ($pairs as $label => [$minKey, $maxKey]) {
            if ((float) $s[$minKey] >= (float) $s[$maxKey]) {
                return back()->withInput()
                    ->with('error', "Nilai Min {$label} harus lebih kecil dari Max {$label}.");
            }
        }

        foreach ($s as $key => $value) {
            ThresholdSetting::where('key', $key)->update(['value' => (float) $value]);
        }

        // Hapus cache agar kalkulasi Fuzzy Sugeno langsung memakai nilai baru
        ThresholdSetting::clearCache();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Pengaturan kondisi ideal berhasil diperbarui.');
    }

    // ── Kembalikan ke nilai default penelitian ────────────────────────────
    public function resetThreshold()
    {
        $defaults = [
            'suhu_min'  => 22,
            'suhu_max'  => 30,
            'udara_min' => 60,
            'udara_max' => 80,
            'tanah_min' => 55,
            'tanah_max' => 75,
        ];

        foreach ($defaults as $key => $value) {
            ThresholdSetting::where('key', $key)->update(['value' => $value]);
        }

        ThresholdSetting::clearCache();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Pengaturan dikembalikan ke nilai default penelitian.');
    }

    // ── Tambah pengguna baru ─────────────────────────────────────────
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
            'password' => $request->password, // otomatis di-hash oleh cast 'hashed' pada model User
            'role'     => $request->role,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    // ── Hapus pengguna ──────────────────────────────────────────────
    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}