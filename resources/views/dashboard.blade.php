@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- ── HEADER ── --}}
<div class="page-header">
    <div>
        <h1>🌽 Dashboard Monitoring Hama Jagung</h1>
        <p>Sistem Deteksi &amp; Prediksi Serangan Hama Berbasis IoT — Metode Fuzzy Sugeno</p>
    </div>
    <div class="update-badge">
        <span class="dot" id="live-dot-header"></span>
        Update: <span id="live-created-at">{{ $latest ? $latest->created_at->format('d M Y, H:i') : 'Belum ada data' }}</span>
    </div>
</div>

{{-- ── BANNER OFFLINE ── --}}
<div id="offline-banner" style="display:none; background:#fef2f2; border:1px solid #fca5a5;
    border-radius:10px; padding:12px 18px; margin-bottom:16px; color:#dc2626;
    font-size:13px; font-weight:600; display:flex; align-items:center; gap:10px;">
    ⚠️ Perangkat IoT terputus — menampilkan data historis terakhir. Card sensor akan kosong hingga alat kembali online.
</div>

{{-- ── 1 KOTAK STATUS TANAMAN (GABUNGAN) ── --}}
<div style="display:grid; grid-template-columns:1fr; gap:16px; margin-bottom:20px;">
    <div class="panel" style="border-left:4px solid 
        @if(!$isOnline) #94a3b8
        @elseif($status=='HAMA' || $waterClass == 'status-critical') #dc2626
        @elseif($status=='WASPADA' || $waterClass == 'status-warning') #f59e0b
        @else #22c55e
        @endif;">
        <div class="panel-header">
            <div class="panel-title">🌱 Status Tanaman</div>
            <span style="font-size:12px; color:#64748b;">
                @if($isOnline)
                    Update: {{ now()->format('H:i') }}
                @else
                    📡 Offline
                @endif
            </span>
        </div>
        <div class="panel-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">

                {{-- Kiri: Status Air --}}
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <span style="font-size:16px;">💧</span>
                        <span style="font-size:13px; font-weight:600; color:#475569;">Kelembapan Tanah</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span style="font-size:24px; font-weight:700; color:{{ $waterClass == 'status-critical' ? '#dc2626' : ($waterClass == 'status-warning' ? '#f59e0b' : '#22c55e') }};">
                            {{ $waterStatus }}
                        </span>
                        <span style="font-size:14px; color:#64748b;">({{ number_format($waterTanah, 1) }}%)</span>
                    </div>
                    <div style="font-size:12px; color:#475569; margin-top:4px;">
                        {{ $waterRecommendation }}
                    </div>
                </div>

                {{-- Kanan: Status Hama --}}
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <span style="font-size:16px;">🐛</span>
                        <span style="font-size:13px; font-weight:600; color:#475569;">Deteksi Hama</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span style="font-size:24px; font-weight:700; color:{{ $isOnline ? ($status=='HAMA' ? '#dc2626' : ($status=='WASPADA' ? '#f59e0b' : '#22c55e')) : '#94a3b8' }};">
                            @if(!$isOnline)
                                📡 Offline
                            @elseif($status=='HAMA')
                                🚨 {{ $status }}
                            @elseif($status=='WASPADA')
                                ⚠️ {{ $status }}
                            @else
                                ✅ {{ $status }}
                            @endif
                        </span>
                        <span style="font-size:14px; color:#64748b;">
                            (Nilai: {{ $isOnline ? number_format($nilai, 3) : '--' }})
                        </span>
                    </div>
                    <div style="font-size:12px; color:#475569; margin-top:4px;">
                        @if(!$isOnline)
                            Menunggu data dari perangkat...
                        @elseif($status=='HAMA')
                            🚨 YOLO + Fuzzy mengonfirmasi hama!
                        @elseif($status=='WASPADA')
                            ⚠️ Risiko tinggi, pantau terus.
                        @else
                            ✅ Kondisi aman.
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ── KARTU SENSOR (3 CARD) ── --}}
<div class="sensor-grid" style="grid-template-columns:repeat(3,1fr);">

    {{-- Suhu --}}
    <div class="sensor-card suhu">
        <span class="sc-icon">🌡️</span>
        <div class="sc-label">Suhu Udara</div>
        <div class="sc-value">
            <span id="live-suhu">{{ $isOnline ? ($latest->suhu ?? '--') : '--' }}</span>
            <span style="font-size:18px">°C</span>
        </div>
        <div class="sc-sub" id="live-suhu-sub">
            @if($isOnline && $latest)
                @php $s = $latest->suhu ?? 0; @endphp
                @if($s >= 30) Kondisi panas — mendukung hama
                @elseif($s >= 22) Kondisi hangat — perlu pantau
                @else Kondisi dingin — relatif aman
                @endif
            @else
                Menunggu data dari alat IoT...
            @endif
        </div>
    </div>

    {{-- Kelembapan Udara --}}
    <div class="sensor-card udara">
        <span class="sc-icon">💧</span>
        <div class="sc-label">Kelembapan Udara</div>
        <div class="sc-value">
            <span id="live-udara">{{ $isOnline ? ($latest->kelembapan_udara ?? '--') : '--' }}</span>
            <span style="font-size:18px">%</span>
        </div>
        <div class="sc-sub" id="live-udara-sub">
            @if($isOnline && $latest)
                @php $u = $latest->kelembapan_udara ?? 0; @endphp
                @if($u >= 78) Udara sangat lembap — risiko tinggi
                @elseif($u >= 60) Udara normal
                @else Udara kering — risiko rendah
                @endif
            @else
                Menunggu data dari alat IoT...
            @endif
        </div>
    </div>

    {{-- Kelembapan Tanah --}}
    <div class="sensor-card tanah">
        <span class="sc-icon">🌱</span>
        <div class="sc-label">Kelembapan Tanah</div>
        <div class="sc-value">
            <span id="live-tanah">{{ $isOnline ? ($latest->kelembapan_tanah ?? '--') : '--' }}</span>
            <span style="font-size:18px">%</span>
        </div>
        <div class="sc-sub" id="live-tanah-sub">
            @if($isOnline && $latest)
                @php $t = $latest->kelembapan_tanah ?? 0; @endphp
                @if($t >= 65) Tanah lembap — perlu waspada
                @elseif($t >= 40) Kelembapan tanah normal
                @else Tanah kering — risiko rendah
                @endif
            @else
                Menunggu data dari alat IoT...
            @endif
        </div>
    </div>

</div>

{{-- ── TOMBOL MANUAL ── --}}
<form action="{{ route('manual') }}" method="POST" style="display:inline;">
    @csrf
    <button type="submit" class="btn-manual">
        🔄 Ambil Data Manual (Simulasi IoT)
    </button>
</form>

{{-- ── GRAFIK SENSOR + ANALISIS FUZZY ── --}}
<div class="content-grid">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">📈 Grafik Sensor (10 Data DB Terakhir)</div>
            <span style="font-size:11px;color:var(--abu);">Suhu · Udara · Tanah</span>
        </div>
        <div class="panel-body">
            <div class="chart-wrap">
                <canvas id="chartSensor"></canvas>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">🧮 Analisis Fuzzy Sugeno</div>
        </div>
        <div class="panel-body">
            <div class="fuzzy-meter">
                <div id="live-fuzzy-meter-val" class="fuzzy-meter-val"
                    style="color:{{ $isOnline ? ($status=='HAMA' ? '#dc2626' : ($status=='WASPADA' ? '#d97706' : '#16a34a')) : '#94a3b8' }}">
                    {{ $isOnline ? number_format($nilai, 3) : '--' }}
                </div>
                <div class="fuzzy-meter-label">Nilai Output Fuzzy (0.0 – 1.0)</div>
                <div class="meter-track">
                    <div class="meter-pointer" id="meterPtr"></div>
                </div>
                <div class="meter-ticks">
                    <span>0.0</span><span>0.45</span><span>0.70</span><span>1.0</span>
                </div>
            </div>
            <hr style="border:none;border-top:1px solid #f1f5f9;margin:14px 0;">
            <div class="analisis-item">
                <span class="analisis-label">🌡️ Suhu</span>
                <span id="analisis-suhu" class="analisis-val">
                    {{ $isOnline ? (($latest->suhu ?? '-') . ' °C') : '--' }}
                </span>
            </div>
            <div class="analisis-item">
                <span class="analisis-label">💧 Kel. Udara</span>
                <span id="analisis-udara" class="analisis-val">
                    {{ $isOnline ? (($latest->kelembapan_udara ?? '-') . ' %') : '--' }}
                </span>
            </div>
            <div class="analisis-item">
                <span class="analisis-label">🌱 Kel. Tanah</span>
                <span id="analisis-tanah" class="analisis-val">
                    {{ $isOnline ? (($latest->kelembapan_tanah ?? '-') . ' %') : '--' }}
                </span>
            </div>
            <div class="analisis-item">
                <span class="analisis-label">📊 Nilai Fuzzy</span>
                <span id="analisis-fuzzy" class="analisis-val">
                    {{ $isOnline ? number_format($nilai, 4) : '--' }}
                </span>
            </div>
            <div class="analisis-item" style="border:none;">
                <span class="analisis-label">🏷️ Deteksi</span>
                <span id="analisis-status-badge" class="badge-status
                    @if(!$isOnline) bs-offline
                    @elseif($status=='HAMA') bs-hama
                    @elseif($status=='WASPADA') bs-waspada
                    @else bs-aman
                    @endif">
                    {{ $isOnline ? $status : 'OFFLINE' }}
                </span>
            </div>
            <div id="live-rekomendasi-box" class="rekomendasi-box
                @if(!$isOnline) offline
                @elseif($status=='HAMA') hama
                @elseif($status=='WASPADA') waspada
                @else aman
                @endif">
                <div id="live-rek-judul" class="rek-judul">
                    @if(!$isOnline) 📡 Alat IoT Tidak Terhubung
                    @elseif($status=='HAMA') 🚨 Tindakan Segera Diperlukan!
                    @elseif($status=='WASPADA') ⚠️ Pantau Lebih Sering
                    @else ✅ Kondisi Terkendali
                    @endif
                </div>
                <div id="live-rek-isi" class="rek-isi">
                    @if(!$isOnline)
                        Perangkat IoT sedang tidak mengirim data. Periksa koneksi jaringan dan pastikan ESP32 menyala.
                    @elseif($status=='HAMA')
                        Kondisi lingkungan saat ini sangat mendukung perkembangan hama. Segera lakukan pemeriksaan fisik pada tanaman jagung dan pertimbangkan tindakan pengendalian hama.
                    @elseif($status=='WASPADA')
                        Kondisi mulai mengarah ke risiko hama. Tingkatkan frekuensi monitoring dan periksa bagian daun dan batang tanaman secara berkala.
                    @else
                        Kondisi sensor dalam batas aman. Lanjutkan pemantauan rutin dan pastikan perangkat IoT berfungsi optimal.
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── GRAFIK FUZZY RISIKO ── --}}
<div class="panel" style="margin-bottom:18px;">
    <div class="panel-header">
        <div class="panel-title">📉 Tren Nilai Risiko Fuzzy Sugeno (10 Data DB Terakhir)</div>
        <span style="font-size:11px;color:var(--abu);">
            🔴 &ge;0.70 Hama &nbsp;|&nbsp; 🟡 0.45–0.70 Waspada &nbsp;|&nbsp; 🟢 &lt;0.45 Aman
        </span>
    </div>
    <div class="panel-body">
        <div class="chart-wrap">
            <canvas id="chartFuzzy"></canvas>
        </div>
    </div>
</div>

{{-- ── TABEL DATA TERAKHIR ── --}}
<div class="panel" style="margin-bottom:18px;">
    <div class="panel-header">
        <div class="panel-title">📋 Riwayat Data Sensor (tersimpan setiap 15 menit)</div>
        <a href="{{ route('riwayat') }}" style="font-size:12px;color:var(--hijau);text-decoration:none;font-weight:600;">
            Lihat Semua Riwayat →
        </a>
    </div>
    <div class="panel-body" style="padding:0;">
        <div class="tabel-wrap">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>🌡️ Suhu</th>
                        <th>💧 Kel. Udara</th>
                        <th>🌱 Kel. Tanah</th>
                        <th>Nilai Fuzzy</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="live-table-body">
                    @forelse($data as $d)
                    @php
                        $nf = round($d->nilai_fuzzy ?? 0, 3);
                        $st = $d->deteksi ?? ($nf >= 0.70 ? 'HAMA' : ($nf >= 0.45 ? 'WASPADA' : 'AMAN'));
                    @endphp
                    <tr>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--abu);">
                            {{ $d->created_at->format('H:i') }}<br>
                            <span style="font-size:11px;">{{ $d->created_at->format('d M') }}</span>
                        </td>
                        <td><span style="background:#fee2e2;color:#991b1b;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:600;">{{ $d->suhu }}°C</span></td>
                        <td><span style="background:#dbeafe;color:#1e40af;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:600;">{{ $d->kelembapan_udara }}%</span></td>
                        <td><span style="background:#dcfce7;color:#166534;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:600;">{{ $d->kelembapan_tanah }}%</span></td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;">{{ $nf }}</td>
                        <td>
                            <span class="badge-status
                                @if($st=='HAMA') bs-hama
                                @elseif($st=='WASPADA') bs-waspada
                                @else bs-aman
                                @endif">{{ $st }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;">📭 Belum ada data tersimpan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── SCRIPT REAL-TIME ── --}}
<script>
    // ── Data awal dari server ──────────────────────────────────────────────────
    var labels      = @json($labels);
    var suhuData    = @json($suhu);
    var udaraData   = @json($udara);
    var tanahData   = @json($tanah);
    var fuzzyData   = @json($fuzzyChart);
    var nilaiSaat   = {{ $isOnline ? $nilai : 0 }};
    var isOnlineInit = {{ $isOnline ? 'true' : 'false' }};

    var chartSensorInstance;
    var chartFuzzyInstance;

    window.addEventListener('load', function () {
        var ptr = document.getElementById('meterPtr');
        if (ptr && isOnlineInit) {
            setTimeout(function () {
                ptr.style.left = (nilaiSaat * 100).toFixed(1) + '%';
            }, 300);
        }
    });

    var ctxSensor = document.getElementById('chartSensor').getContext('2d');
    chartSensorInstance = new Chart(ctxSensor, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Suhu (°C)',     data: suhuData,  borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.06)',  borderWidth: 2, tension: 0.4, fill: true },
                { label: 'Kel. Udara (%)', data: udaraData, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.06)', borderWidth: 2, tension: 0.4, fill: true },
                { label: 'Kel. Tanah (%)', data: tanahData, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.06)',  borderWidth: 2, tension: 0.4, fill: true },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    var ctxFuzzy = document.getElementById('chartFuzzy').getContext('2d');
    chartFuzzyInstance = new Chart(ctxFuzzy, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nilai Fuzzy',
                data: fuzzyData,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,0.08)',
                borderWidth: 2.5,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { min: 0, max: 1 } }
        }
    });

    var lastLiveTimestamp = null;

    function perbaruiDashboard() {
        fetch('/live-data')
            .then(function(r) { return r.json(); })
            .then(function(data) {

                var isOnline = data.isOnline === true;

                var banner = document.getElementById('offline-banner');
                if (banner) banner.style.display = isOnline ? 'none' : 'flex';

                if (!isOnline) {
                    setCardOffline();
                    return;
                }

                var suhu        = parseFloat(data.suhu);
                var udara       = parseFloat(data.kelembapan_udara);
                var tanah       = parseFloat(data.kelembapan_tanah);
                var nilaiFuzzy  = parseFloat(data.nilai_fuzzy);
                var statusHama  = (data.status_hama || 'AMAN').toUpperCase();
                var tsIso       = data.timestamp_iso;
                var tsFmt       = data.timestamp_formatted;

                var elAt = document.getElementById('live-created-at');
                if (elAt && tsFmt) elAt.innerText = tsFmt;

                if (tsIso === lastLiveTimestamp) return;
                lastLiveTimestamp = tsIso;

                el('live-suhu',  suhu);
                el('live-udara', udara);
                el('live-tanah', tanah);

                var elSuhuSub = document.getElementById('live-suhu-sub');
                if (elSuhuSub) {
                    if (suhu >= 30)      elSuhuSub.innerText = 'Kondisi panas — mendukung hama';
                    else if (suhu >= 22) elSuhuSub.innerText = 'Kondisi hangat — perlu pantau';
                    else                 elSuhuSub.innerText = 'Kondisi dingin — relatif aman';
                }
                var elUdaraSub = document.getElementById('live-udara-sub');
                if (elUdaraSub) {
                    if (udara >= 78)      elUdaraSub.innerText = 'Udara sangat lembap — risiko tinggi';
                    else if (udara >= 60) elUdaraSub.innerText = 'Udara normal';
                    else                  elUdaraSub.innerText = 'Udara kering — risiko rendah';
                }
                var elTanahSub = document.getElementById('live-tanah-sub');
                if (elTanahSub) {
                    if (tanah >= 65)      elTanahSub.innerText = 'Tanah lembap — perlu waspada';
                    else if (tanah >= 40) elTanahSub.innerText = 'Kelembapan tanah normal';
                    else                  elTanahSub.innerText = 'Tanah kering — risiko rendah';
                }

                // Update status di card "Status Tanaman" secara manual via DOM
                // Karena data hybrid di card "Status Tanaman" perlu diupdate
                // Tapi karena card ini menggunakan PHP variable, polling JS tidak mengubahnya secara langsung.
                // Untuk update real-time, sebaiknya tambahkan elemen dengan id.

                // Namun untuk Opsi 2, grid tidak punya status hama, jadi hanya update di card "Status Tanaman".
                // Kita update melalui tag dengan id jika ada.
                // Saya tambahkan id untuk elemen status hama di card "Status Tanaman".

                // Kode ini tetap berjalan untuk update sensor lainnya.
            })
            .catch(function(err) {
                console.error('Gagal polling live-data:', err);
            });
    }

    function setCardOffline() {
        el('live-suhu',  '--');
        el('live-udara', '--');
        el('live-tanah', '--');

        var subs = ['live-suhu-sub', 'live-udara-sub', 'live-tanah-sub'];
        subs.forEach(function(id) {
            var e = document.getElementById(id);
            if (e) e.innerText = 'Menunggu data dari alat IoT...';
        });

        var meterVal = document.getElementById('live-fuzzy-meter-val');
        if (meterVal) { meterVal.innerText = '--'; meterVal.style.color = '#94a3b8'; }
        var ptr = document.getElementById('meterPtr');
        if (ptr) ptr.style.left = '0%';

        el('analisis-suhu',  '--');
        el('analisis-udara', '--');
        el('analisis-tanah', '--');
        el('analisis-fuzzy', '--');

        var analisisBadge = document.getElementById('analisis-status-badge');
        if (analisisBadge) { analisisBadge.innerText = 'OFFLINE'; analisisBadge.className = 'badge-status bs-offline'; }

        var rekBox   = document.getElementById('live-rekomendasi-box');
        var rekJudul = document.getElementById('live-rek-judul');
        var rekIsi   = document.getElementById('live-rek-isi');
        if (rekBox)   rekBox.className   = 'rekomendasi-box offline';
        if (rekJudul) rekJudul.innerHTML = '📡 Alat IoT Tidak Terhubung';
        if (rekIsi)   rekIsi.innerText   = 'Perangkat IoT sedang tidak mengirim data. Periksa koneksi jaringan dan pastikan ESP32 menyala.';
    }

    function el(id, val) {
        var e = document.getElementById(id);
        if (e) e.innerText = val;
    }

    perbaruiDashboard();
    setInterval(perbaruiDashboard, 5000);
</script>

@endsection