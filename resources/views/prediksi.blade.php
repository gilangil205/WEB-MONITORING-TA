@extends('layouts.app')

@section('title','Prediksi Serangan Hama')

@section('content')

{{-- ── HEADER ── --}}
<div class="page-header">
    <div>
        <h1>📊 Prediksi Serangan Hama</h1>
        <p>Proyeksi risiko 3 periode ke depan berdasarkan tren data sensor — Metode Fuzzy Sugeno</p>
    </div>
    <div class="update-badge">
        <span class="dot"></span>
        Data DB: {{ $latest ? $latest->created_at->format('d M Y, H:i') : 'Belum ada data' }}
        @if($isOnline)
            &nbsp;|&nbsp; <span style="color:#16a34a;font-weight:700;">● IoT Online</span>
        @else
            &nbsp;|&nbsp; <span style="color:#dc2626;font-weight:700;">● IoT Offline</span>
        @endif
    </div>
</div>

{{-- ── KARTU DATA LIVE (dari Cache IoT) ── --}}
<div class="card-box">

    <div class="card-item gradient-red">
        <h4>🌡️ Suhu</h4>
        <p id="pred-suhu">
            @if($isOnline) {{ $latest->suhu ?? '--' }}°C @else --°C @endif
        </p>
        <small>Data terkini IoT</small>
    </div>

    <div class="card-item gradient-blue">
        <h4>💧 Kel. Udara</h4>
        <p id="pred-udara">
            @if($isOnline) {{ $latest->kelembapan_udara ?? '--' }}% @else --% @endif
        </p>
        <small>Kelembapan udara</small>
    </div>

    <div class="card-item gradient-green">
        <h4>🌱 Kel. Tanah</h4>
        <p id="pred-tanah">
            @if($isOnline) {{ $latest->kelembapan_tanah ?? '--' }}% @else --% @endif
        </p>
        <small>Kelembapan tanah</small>
    </div>

    <div id="pred-status-card" class="card-item
        @if(!$isOnline) status-offline
        @elseif($status=='HAMA') status-high
        @elseif($status=='WASPADA') status-medium
        @else status-low
        @endif">
        <h4>⚠️ Status Saat Ini</h4>
        <p id="pred-status-text">{{ $isOnline ? $status : 'OFFLINE' }}</p>
        <small>Nilai Fuzzy: <span id="pred-fuzzy-val">{{ $isOnline ? number_format($nilai, 3) : '--' }}</span></small>
    </div>

</div>

{{-- ── GRID PREDIKSI ── --}}
<div class="grid-prediksi">

    {{-- GRAFIK HISTORIS + PREDIKSI --}}
    <div class="section">
        <h3 style="margin-bottom:15px;">📈 Historis &amp; Prediksi 3 Periode ke Depan</h3>
        <div class="chart-wrap" style="height:280px;">
            <canvas id="chartPrediksi"></canvas>
        </div>
        <p style="margin-top:10px;font-size:12px;color:#64748b;">
            * Garis putus-putus = prediksi berdasarkan tren rata-rata (Fuzzy Sugeno) · Interval DB: setiap 15 menit
        </p>
    </div>

    {{-- ANALISIS & TABEL PREDIKSI --}}
    <div class="section">
        <h3 style="margin-bottom:15px;">🧠 Analisis Prediksi</h3>

        {{-- Status saat ini --}}
        <div id="pred-analisis-box" style="padding:12px 16px;border-radius:10px;margin-bottom:16px;
            background:{{ !$isOnline ? '#f8fafc' : ($status=='HAMA' ? '#fee2e2' : ($status=='WASPADA' ? '#fef9c3' : '#dcfce7')) }};
            border-left:4px solid {{ !$isOnline ? '#94a3b8' : ($status=='HAMA' ? '#ef4444' : ($status=='WASPADA' ? '#facc15' : '#22c55e')) }};">
            @if(!$isOnline)
                <b style="color:#64748b;">📡 Alat IoT Tidak Terhubung</b>
                <p style="margin-top:4px;font-size:13px;color:#64748b;">
                    Perangkat IoT sedang offline. Prediksi dihitung dari data historis DB terakhir.
                </p>
            @elseif($status == 'HAMA')
                <b style="color:#dc2626;">🚨 Risiko Tinggi!</b>
                <p style="margin-top:4px;font-size:13px;color:#7f1d1d;">
                    Kondisi saat ini sangat mendukung serangan hama. Segera lakukan pemeriksaan dan penanganan.
                </p>
            @elseif($status == 'WASPADA')
                <b style="color:#854d0e;">⚠️ Perlu Waspada</b>
                <p style="margin-top:4px;font-size:13px;color:#713f12;">
                    Kondisi mulai mendukung pertumbuhan hama. Lakukan monitoring lebih sering.
                </p>
            @else
                <b style="color:#166534;">✅ Kondisi Aman</b>
                <p style="margin-top:4px;font-size:13px;color:#14532d;">
                    Risiko serangan hama rendah. Tetap lakukan pemantauan rutin.
                </p>
            @endif
        </div>

        {{-- Tabel prediksi 3 periode --}}
        <h4 style="margin-bottom:10px;font-size:14px;color:#475569;">Prediksi 3 Periode ke Depan (±15 Menit):</h4>

        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e2e8f0;">Periode</th>
                    <th style="padding:8px 10px;text-align:center;border-bottom:2px solid #e2e8f0;">Nilai Fuzzy</th>
                    <th style="padding:8px 10px;text-align:center;border-bottom:2px solid #e2e8f0;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prediksi as $i => $p)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:8px 10px;">+{{ ($i+1) * 15 }} Menit</td>
                    <td style="padding:8px 10px;text-align:center;font-weight:600;">
                        {{ number_format($p, 3) }}
                    </td>
                    <td style="padding:8px 10px;text-align:center;">
                        <span class="badge-status
                            @if($prediksiStatus[$i]=='HAMA') bs-hama
                            @elseif($prediksiStatus[$i]=='WASPADA') bs-waspada
                            @else bs-aman
                            @endif">
                            {{ $prediksiStatus[$i] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Keterangan threshold --}}
        <div style="margin-top:16px;font-size:12px;color:#64748b;border-top:1px solid #e2e8f0;padding-top:12px;">
            <p><b>Keterangan Threshold Fuzzy Sugeno:</b></p>
            <p style="margin-top:4px;">🔴 <b>HAMA</b> &nbsp;&nbsp;: Nilai &ge; 0.70</p>
            <p style="margin-top:2px;">🟡 <b>WASPADA</b> : 0.45 &le; Nilai &lt; 0.70</p>
            <p style="margin-top:2px;">🟢 <b>AMAN</b> &nbsp;&nbsp;: Nilai &lt; 0.45</p>
            <p style="margin-top:8px;color:#94a3b8;">ℹ️ Data historis tersimpan ke DB setiap 15 menit. Grafik live dari Cache IoT (setiap 5 menit).</p>
        </div>
    </div>

</div>

<script>
/* ── DATA DARI CONTROLLER ── */
var labelsHistoris = @json($labelsHistoris);
var fuzzyHistoris  = @json($fuzzyValues);
var prediksiValues = @json($prediksi);

/* ── GABUNGKAN LABEL ── */
var allLabels = labelsHistoris.concat(['+15 Mnt', '+30 Mnt', '+45 Mnt']);

/* ── DATASET HISTORIS ── */
var dataHistoris = fuzzyHistoris.concat([null, null, null]);

/* ── DATASET PREDIKSI (sambung dari titik terakhir historis) ── */
var joinPoint   = fuzzyHistoris.length > 0 ? fuzzyHistoris[fuzzyHistoris.length - 1] : 0;
var nullsBefore = [];
for (var i = 0; i < fuzzyHistoris.length - 1; i++) { nullsBefore.push(null); }
var dataPrediksi = nullsBefore.concat([joinPoint]).concat(prediksiValues);

/* ── RENDER CHART ── */
var ctx = document.getElementById('chartPrediksi').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: allLabels,
        datasets: [
            {
                label: 'Historis Fuzzy (DB)',
                data: dataHistoris,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.08)',
                borderWidth: 2.5,
                pointRadius: 4,
                tension: 0.3,
                fill: true,
                spanGaps: false
            },
            {
                label: 'Prediksi ±15 Mnt',
                data: dataPrediksi,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.08)',
                borderWidth: 2.5,
                borderDash: [6, 4],
                pointRadius: 5,
                pointBackgroundColor: '#ef4444',
                tension: 0.3,
                fill: false,
                spanGaps: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                callbacks: {
                    label: function (ctx) {
                        return ' Nilai: ' + (ctx.parsed.y !== null ? ctx.parsed.y.toFixed(3) : '-');
                    }
                }
            }
        },
        scales: {
            y: {
                min: 0, max: 1,
                ticks: { stepSize: 0.1, callback: function(v){ return v.toFixed(1); } },
                title: { display: true, text: 'Nilai Fuzzy (0-1)' }
            },
            x: { title: { display: true, text: 'Waktu' } }
        }
    }
});

/* ── POLLING LIVE: perbarui card atas setiap 5 detik dari Cache IoT ── */
function perbaruiPrediksiCard() {
    fetch('/live-data')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var isOnline = data.isOnline === true;

            // Kartu atas
            var elSuhu   = document.getElementById('pred-suhu');
            var elUdara  = document.getElementById('pred-udara');
            var elTanah  = document.getElementById('pred-tanah');
            var elStatus = document.getElementById('pred-status-text');
            var elFuzzy  = document.getElementById('pred-fuzzy-val');
            var elCard   = document.getElementById('pred-status-card');

            if (!isOnline) {
                if (elSuhu)   elSuhu.innerText  = '--°C';
                if (elUdara)  elUdara.innerText  = '--%';
                if (elTanah)  elTanah.innerText  = '--%';
                if (elStatus) elStatus.innerText = 'OFFLINE';
                if (elFuzzy)  elFuzzy.innerText  = '--';
                if (elCard)   elCard.className   = 'card-item status-offline';
                return;
            }

            if (elSuhu)   elSuhu.innerText  = data.suhu + '°C';
            if (elUdara)  elUdara.innerText  = data.kelembapan_udara + '%';
            if (elTanah)  elTanah.innerText  = data.kelembapan_tanah + '%';
            if (elStatus) elStatus.innerText = data.status_hama;
            if (elFuzzy)  elFuzzy.innerText  = parseFloat(data.nilai_fuzzy).toFixed(3);

            if (elCard) {
                var cls = 'card-item ';
                if      (data.status_hama === 'HAMA')    cls += 'status-high';
                else if (data.status_hama === 'WASPADA') cls += 'status-medium';
                else                                     cls += 'status-low';
                elCard.className = cls;
            }
        })
        .catch(function(err) { console.error('Gagal polling prediksi card:', err); });
}

perbaruiPrediksiCard();
setInterval(perbaruiPrediksiCard, 5000);
</script>

@endsection