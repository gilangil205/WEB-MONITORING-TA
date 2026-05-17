@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Kita tidak menggunakan @extends, @section, atau @endsection karena Breeze menggunakan Component Layout --}}
    
        {{-- ── KODE ASLI DASHBOARD KAMU DIMULAI DARI SINI (TIDAK ADA PERUBAHAN) ── --}}

        {{-- ── HEADER ── --}}
        <div class="page-header">
            <div>
                <h1>🌽 Dashboard Monitoring Hama Jagung</h1>
                <p>Sistem Deteksi &amp; Prediksi Serangan Hama Berbasis IoT — Metode Fuzzy Sugeno</p>
            </div>
            <div class="update-badge">
                <span class="dot"></span>
                Update: {{ $latest ? $latest->created_at->format('d M Y, H:i') : 'Belum ada data' }}
            </div>
        </div>

        {{-- ── KARTU SENSOR ── --}}
        <div class="sensor-grid">
            <div class="sensor-card suhu">
                <span class="sc-icon">🌡️</span>
                <div class="sc-label">Suhu Udara</div>
                <div class="sc-value">{{ $latest->suhu ?? '--' }}<span style="font-size:18px">°C</span></div>
                <div class="sc-sub">
                    @php $s = $latest->suhu ?? 0; @endphp
                    @if($s >= 30) Kondisi panas — mendukung hama
                    @elseif($s >= 22) Kondisi hangat — perlu pantau
                    @else Kondisi dingin — relatif aman
                    @endif
                </div>
            </div>

            <div class="sensor-card udara">
                <span class="sc-icon">💧</span>
                <div class="sc-label">Kelembapan Udara</div>
                <div class="sc-value">{{ $latest->kelembapan_udara ?? '--' }}<span style="font-size:18px">%</span></div>
                <div class="sc-sub">
                    @php $u = $latest->kelembapan_udara ?? 0; @endphp
                    @if($u >= 78) Udara sangat lembap — risiko tinggi
                    @elseif($u >= 60) Udara normal
                    @else Udara kering — risiko rendah
                    @endif
                </div>
            </div>

            <div class="sensor-card tanah">
                <span class="sc-icon">🌱</span>
                <div class="sc-label">Kelembapan Tanah</div>
                <div class="sc-value">{{ $latest->kelembapan_tanah ?? '--' }}<span style="font-size:18px">%</span></div>
                <div class="sc-sub">
                    @php $t = $latest->kelembapan_tanah ?? 0; @endphp
                    @if($t >= 65) Tanah lembap — perlu waspada
                    @elseif($t >= 40) Kelembapan tanah normal
                    @else Tanah kering — risiko rendah
                    @endif
                </div>
            </div>

            <div class="sensor-card status
                @if($status=='HAMA') status-hama
                @elseif($status=='WASPADA') status-waspada
                @else status-aman
                @endif">
                <span class="sc-icon">
                    @if($status=='HAMA') 🚨 @elseif($status=='WASPADA') ⚠️ @else ✅ @endif
                </span>
                <div class="sc-label">Status Deteksi Hama</div>
                <div class="sc-value" style="font-size:22px; margin-bottom:4px;">
                    @if($status=='HAMA') Terdeteksi!
                    @elseif($status=='WASPADA') Waspada
                    @else Aman
                    @endif
                </div>
                <div>
                    <span class="status-badge-besar
                        @if($status=='HAMA') badge-hama
                        @elseif($status=='WASPADA') badge-waspada
                        @else badge-aman
                        @endif">
                        {{ $status }} | {{ number_format($nilai, 3) }}
                    </span>
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
                    <div class="panel-title">📈 Grafik Sensor Real-Time (10 Data Terakhir)</div>
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
                        <div class="fuzzy-meter-val"
                            style="color:{{ $status=='HAMA' ? '#dc2626' : ($status=='WASPADA' ? '#d97706' : '#16a34a') }}">
                            {{ number_format($nilai, 3) }}
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
                        <span class="analisis-val">{{ $latest->suhu ?? '-' }} °C</span>
                    </div>
                    <div class="analisis-item">
                        <span class="analisis-label">💧 Kel. Udara</span>
                        <span class="analisis-val">{{ $latest->kelembapan_udara ?? '-' }} %</span>
                    </div>
                    <div class="analisis-item">
                        <span class="analisis-label">🌱 Kel. Tanah</span>
                        <span class="analisis-val">{{ $latest->kelembapan_tanah ?? '-' }} %</span>
                    </div>
                    <div class="analisis-item">
                        <span class="analisis-label">📊 Nilai Fuzzy</span>
                        <span class="analisis-val">{{ number_format($nilai, 4) }}</span>
                    </div>
                    <div class="analisis-item" style="border:none;">
                        <span class="analisis-label">🏷️ Deteksi</span>
                        <span class="badge-status
                            @if($status=='HAMA') bs-hama
                            @elseif($status=='WASPADA') bs-waspada
                            @else bs-aman
                            @endif">
                            {{ $status }}
                        </span>
                    </div>
                    <div class="rekomendasi-box
                        @if($status=='HAMA') hama
                        @elseif($status=='WASPADA') waspada
                        @else aman
                        @endif">
                        <div class="rek-judul">
                            @if($status=='HAMA') 🚨 Tindakan Segera Diperlukan!
                            @elseif($status=='WASPADA') ⚠️ Pantau Lebih Sering
                            @else ✅ Kondisi Terkendali
                            @endif
                        </div>
                        <div class="rek-isi">
                            @if($status=='HAMA')
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
                <div class="panel-title">📉 Tren Nilai Risiko Fuzzy Sugeno (10 Data Terakhir)</div>
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

        {{-- ── TABEL DATA TERAKHIR (DIPOTONG AGAR RINGKAS) ── --}}
        <div class="panel" style="margin-bottom:18px;">
            <div class="panel-header">
                <div class="panel-title">📋 Data Sensor Terbaru</div>
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
                        <tbody>
                            @forelse($data as $d)
                            @php
                                // ... Logika Fuzzy didalam tabel tetap sama ...
                                $sv=$d->suhu; $uv=$d->kelembapan_udara; $tv=$d->kelembapan_tanah;
                                $din=max(0,min(1,(25-$sv)/5));
                                $han=max(0,min(1,min(($sv-22)/5,(32-$sv)/5)));
                                $pan=max(0,min(1,($sv-30)/5));
                                $ku=max(0,min(1,(65-$uv)/15));
                                $nu=max(0,min(1,min(($uv-60)/12,(85-$uv)/13)));
                                $lu=max(0,min(1,($uv-78)/12));
                                $kt=max(0,min(1,(50-$tv)/20));
                                $nt=max(0,min(1,min(($tv-40)/20,(80-$tv)/20)));
                                $lt=max(0,min(1,($tv-65)/20));
                                $rls=[[min($pan,$lu,$lt),1.00],[min($pan,$lu,$nt),0.85],[min($pan,$lu,$kt),0.75],
                                      [min($han,$lu,$lt),0.80],[min($han,$lu,$nt),0.65],[min($pan,$nu,$lt),0.70],
                                      [min($pan,$nu,$nt),0.55],[min($han,$nu,$nt),0.40],[min($han,$nu,$kt),0.30],
                                      [min($din,$ku,$kt),0.10],[min($din,$nu,$nt),0.20],[min($pan,$ku,$kt),0.30],
                                      [min($din,$lu,$lt),0.45],[min($han,$ku,$kt),0.20]];
                                $nn=0; $dd=0;
                                foreach($rls as $rl){ $nn+=$rl[0]*$rl[1]; $dd+=$rl[0]; }
                                $nf=round($dd==0?0:$nn/$dd, 3);
                                $st=$nf>=0.70?'HAMA':($nf>=0.45?'WASPADA':'AMAN');
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
                            <tr><td colspan="6" style="text-align:center;">📭 Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── SCRIPT (SAMA PERSIS) ── --}}
        <script>
            // ... Seluruh Script ChartJS kamu tempel di sini ...
            var labels = @json($labels);
            var suhuData = @json($suhu);
            var udaraData = @json($udara);
            var tanahData = @json($tanah);
            var fuzzyData = @json($fuzzyChart);
            var nilaiSaat = {{ $nilai }};

            window.addEventListener('load', function () {
                var ptr = document.getElementById('meterPtr');
                if (ptr) {
                    setTimeout(function () {
                        ptr.style.left = (nilaiSaat * 100).toFixed(1) + '%';
                    }, 300);
                }
            });

            var ctxSensor = document.getElementById('chartSensor').getContext('2d');
            new Chart(ctxSensor, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Suhu (°C)', data: suhuData, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.06)', borderWidth: 2, tension: 0.4, fill: true },
                        { label: 'Kel. Udara (%)', data: udaraData, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.06)', borderWidth: 2, tension: 0.4, fill: true },
                        { label: 'Kel. Tanah (%)', data: tanahData, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.06)', borderWidth: 2, tension: 0.4, fill: true }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            var ctxFuzzy = document.getElementById('chartFuzzy').getContext('2d');
            new Chart(ctxFuzzy, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{ label: 'Nilai Fuzzy', data: fuzzyData, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.08)', borderWidth: 2.5, tension: 0.4, fill: true }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 1 } } }
            });
        </script>

@endsection