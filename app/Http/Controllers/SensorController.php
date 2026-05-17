<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorReading;
use Carbon\Carbon;

class SensorController extends Controller
{
    // ================== HELPER: AMBIL STATUS GLOBAL ==================
    private function getStatusGlobal()
    {
        $latest = SensorReading::latest()->first();
        $statusGlobal = 'AMAN';

        if ($latest) {
            $nilai = $this->fuzzySugeno(
                $latest->suhu,
                $latest->kelembapan_udara,
                $latest->kelembapan_tanah
            );
            [$statusGlobal] = $this->getStatus($nilai);
        }

        return $statusGlobal;
    }

    // ================== STORE DATA (dari IoT) ==================
    public function store(Request $request)
    {
        // Validasi token API sederhana
        $token = $request->header('X-API-TOKEN') ?? $request->input('api_token');
        if ($token !== env('IOT_API_TOKEN', 'smartfarm-secret-token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'suhu'              => 'required|numeric|between:0,60',
            'kelembapan_udara'  => 'required|numeric|between:0,100',
            'kelembapan_tanah'  => 'required|numeric|between:0,100',
            'image'             => 'nullable|image|max:5120'
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('kamera', 'public');
        }

        // Bulatkan ke kelipatan 10 menit
        $now    = Carbon::now();
        $minute = floor($now->minute / 10) * 10;
        $timeRounded = $now->copy()->setMinute($minute)->setSecond(0);

        $nilai  = $this->fuzzySugeno(
            $request->suhu,
            $request->kelembapan_udara,
            $request->kelembapan_tanah
        );

        [$status]  = $this->getStatus($nilai);

        SensorReading::create([
            'suhu'             => $request->suhu,
            'kelembapan_udara' => $request->kelembapan_udara,
            'kelembapan_tanah' => $request->kelembapan_tanah,
            'image'            => $path,
            'deteksi'          => $status,
            'created_at'       => $timeRounded,
        ]);

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'status'  => $status,
            'nilai'   => round($nilai, 4),
        ], 201);
    }

    // ================== MANUAL (simulasi data) ==================
    public function manual()
    {
        $suhu   = rand(22, 36);
        $udara  = rand(55, 95);
        $tanah  = rand(35, 90);

        $nilai   = $this->fuzzySugeno($suhu, $udara, $tanah);
        [$status] = $this->getStatus($nilai);

        SensorReading::create([
            'suhu'             => $suhu,
            'kelembapan_udara' => $udara,
            'kelembapan_tanah' => $tanah,
            'deteksi'          => $status,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Data manual berhasil ditambahkan');
    }

    // ================== FUZZY SUGENO ==================
    /**
     * Metode Fuzzy Sugeno untuk menghitung risiko serangan hama tanaman jagung.
     *
     * Variabel input:
     *   - Suhu         : 20–40°C
     *   - Kelembapan Udara : 50–100%
     *   - Kelembapan Tanah : 30–100%
     *
     * Output (crisp): 0.0 (sangat aman) — 1.0 (sangat berisiko)
     */
    private function fuzzySugeno($suhu, $udara, $tanah)
    {
        // --- Fuzzifikasi Suhu ---
        // Dingin  : turun dari 25 ke 20
        $dingin = max(0, min(1, (25 - $suhu) / 5));
        // Hangat  : naik dari 22, turun dari 32 (segitiga puncak 27)
        $hangat = max(0, min(1, min(($suhu - 22) / 5, (32 - $suhu) / 5)));
        // Panas   : naik dari 30
        $panas  = max(0, min(1, ($suhu - 30) / 5));

        // --- Fuzzifikasi Kelembapan Udara ---
        // Kering  : < 65%
        $kering_u = max(0, min(1, (65 - $udara) / 15));
        // Normal  : segitiga 60–85, puncak 72
        $normal_u = max(0, min(1, min(($udara - 60) / 12, (85 - $udara) / 13)));
        // Lembap  : > 80%
        $lembap_u = max(0, min(1, ($udara - 78) / 12));

        // --- Fuzzifikasi Kelembapan Tanah ---
        // Kering  : < 50%
        $kering_t = max(0, min(1, (50 - $tanah) / 20));
        // Normal  : segitiga 40–80, puncak 60
        $normal_t = max(0, min(1, min(($tanah - 40) / 20, (80 - $tanah) / 20)));
        // Lembap  : > 70%
        $lembap_t = max(0, min(1, ($tanah - 65) / 20));

        // --- Rules Fuzzy Sugeno ---
        // Format: [firing_strength, output_crisp_z]
        // z = 1.00 => HAMA    (risiko sangat tinggi)
        // z = 0.65 => WASPADA (risiko sedang)
        // z = 0.10 => AMAN    (risiko rendah)
        $rules = [
            // Kondisi sangat berisiko (panas + lembap udara + lembap tanah)
            [min($panas,  $lembap_u,  $lembap_t),   1.00],
            // Panas + lembap udara (tanpa memandang tanah)
            [min($panas,  $lembap_u,  $normal_t),   0.85],
            [min($panas,  $lembap_u,  $kering_t),   0.75],
            // Hangat + lembap udara
            [min($hangat, $lembap_u,  $lembap_t),   0.80],
            [min($hangat, $lembap_u,  $normal_t),   0.65],
            // Panas + normal udara
            [min($panas,  $normal_u,  $lembap_t),   0.70],
            [min($panas,  $normal_u,  $normal_t),   0.55],
            // Hangat + normal udara
            [min($hangat, $normal_u,  $normal_t),   0.40],
            [min($hangat, $normal_u,  $kering_t),   0.30],
            // Kondisi aman (dingin atau kering)
            [min($dingin, $kering_u,  $kering_t),   0.10],
            [min($dingin, $normal_u,  $normal_t),   0.20],
            [min($panas,  $kering_u,  $kering_t),   0.30],
            [min($dingin, $lembap_u,  $lembap_t),   0.45],
            [min($hangat, $kering_u,  $kering_t),   0.20],
        ];

        $numerator   = 0;
        $denominator = 0;

        foreach ($rules as [$r, $z]) {
            $numerator   += $r * $z;
            $denominator += $r;
        }

        return $denominator == 0 ? 0 : $numerator / $denominator;
    }

    // ================== STATUS ==================
    private function getStatus($nilai)
    {
        if ($nilai >= 0.70) return ['HAMA',    'status-high'];
        if ($nilai >= 0.45) return ['WASPADA', 'status-medium'];
        return                     ['AMAN',    'status-low'];
    }


    // ================== DASHBOARD ==================
    public function index()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $latest = SensorReading::latest()->first();
        $data   = SensorReading::latest()->take(10)->get()->reverse()->values();

        $nilai  = 0;
        $status = 'AMAN';
        $class  = 'status-low';

        if ($latest) {
            $nilai = $this->fuzzySugeno(
                $latest->suhu,
                $latest->kelembapan_udara,
                $latest->kelembapan_tanah
            );
            [$status, $class] = $this->getStatus($nilai);
        }

        $labels = $data->pluck('created_at')->map(fn($d) => $d->format('H:i'))->values();
        $suhu   = $data->pluck('suhu')->values();
        $udara  = $data->pluck('kelembapan_udara')->values();
        $tanah  = $data->pluck('kelembapan_tanah')->values();

        // Hitung nilai fuzzy tiap data untuk grafik risiko
        $fuzzyChart = $data->map(fn($d) => round($this->fuzzySugeno(
            $d->suhu, $d->kelembapan_udara, $d->kelembapan_tanah
        ), 3))->values();

        return view('dashboard', compact(
            'latest', 'data', 'nilai', 'status', 'class',
            'labels', 'suhu', 'udara', 'tanah', 'fuzzyChart'
        ));
    }

    // ================== MONITORING (redirect) ==================
    public function monitoring()
    {
        return redirect()->route('dashboard');
    }

    // ================== PREDIKSI ==================
    public function prediksi()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $latest = SensorReading::latest()->first();
        $data   = SensorReading::latest()->take(10)->get()->reverse()->values();

        // Hitung nilai fuzzy historis
        $fuzzyValues = $data->map(fn($d) => $this->fuzzySugeno(
            $d->suhu, $d->kelembapan_udara, $d->kelembapan_tanah
        ))->values()->toArray();

        $nilai  = count($fuzzyValues) ? end($fuzzyValues) : 0;
        [$status, $class] = $this->getStatus($nilai);

        // Hitung rata-rata trend (moving average selisih)
        $diff = 0;
        if (count($fuzzyValues) > 1) {
            $totalDiff = 0;
            for ($i = 1; $i < count($fuzzyValues); $i++) {
                $totalDiff += ($fuzzyValues[$i] - $fuzzyValues[$i - 1]);
            }
            $diff = $totalDiff / (count($fuzzyValues) - 1);
        }

        // Prediksi 3 periode ke depan (linear trend)
        $prediksi        = [];
        $prediksiStatus  = [];
        $last            = $nilai;

        for ($i = 1; $i <= 3; $i++) {
            $next = max(0, min(1, $last + $diff));
            $prediksi[]       = round($next, 3);
            [$ps]             = $this->getStatus($next);
            $prediksiStatus[] = $ps;
            $last             = $next;
        }

        // Label grafik historis
        $labelsHistoris = $data->pluck('created_at')
            ->map(fn($d) => $d->format('H:i'))->values()->toArray();

        return view('prediksi', compact(
            'latest', 'nilai', 'status', 'class',
            'prediksi', 'prediksiStatus',
            'fuzzyValues', 'labelsHistoris'
        ));
    }

    // ================== RIWAYAT ==================
    public function riwayat()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $query = SensorReading::latest();

        // Filter waktu
        $filter = request('filter');
        if ($filter === '7hari') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($filter === '1bulan') {
            $query->where('created_at', '>=', now()->subMonth());
        } elseif ($filter === '3bulan') {
            $query->where('created_at', '>=', now()->subMonths(3));
        }

        // Filter deteksi
        $filterDeteksi = request('deteksi');
        if (in_array($filterDeteksi, ['HAMA', 'WASPADA', 'AMAN'])) {
            $query->where('deteksi', $filterDeteksi);
        }

        $data = $query->paginate(10);

        $data->getCollection()->transform(function ($item) {
            $nilai = $this->fuzzySugeno(
                $item->suhu,
                $item->kelembapan_udara,
                $item->kelembapan_tanah
            );
            [$status] = $this->getStatus($nilai);

            $item->nilai   = round($nilai, 3);
            $item->status  = $status;  // HAMA / WASPADA / AMAN
            $item->deteksi = $status;  // sama — sudah seragam

            return $item;
        });

        return view('riwayat', compact('data'));
    }

    // ================== KAMERA ==================
    public function kamera()
    {
        $statusGlobal = $this->getStatusGlobal();
        view()->share('statusGlobal', $statusGlobal);

        $latest  = SensorReading::latest()->first();
        $nilai   = 0;
        $status  = 'AMAN';
        $class   = 'status-low';

        if ($latest) {
            $nilai = $this->fuzzySugeno(
                $latest->suhu,
                $latest->kelembapan_udara,
                $latest->kelembapan_tanah
            );
            [$status, $class] = $this->getStatus($nilai);
        }

        return view('kamera', compact('latest', 'nilai', 'status', 'class'));
    }
}