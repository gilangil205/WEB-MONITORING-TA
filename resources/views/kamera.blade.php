@extends('layouts.app')

@section('title','Monitoring Kamera')

@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

:root {
    --hijau:      #16a34a;
    --hijau-muda: #22c55e;
    --kuning:     #d97706;
    --merah:      #dc2626;
    --biru:       #2563eb;
    --abu:        #64748b;
    --bg:         #f0fdf4;
    --card:       #ffffff;
    --border:     #dcfce7;
    --teks:       #0f172a;
    --teks2:      #475569;
    --radius:     14px;
    --shadow:     0 4px 24px rgba(0,0,0,0.07);
}

body { background: var(--bg); font-family: 'Space Grotesk', sans-serif; }

.page-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
.page-header h1 { font-size:22px; font-weight:700; color:var(--teks); margin-bottom:2px; }
.page-header p  { font-size:13px; color:var(--abu); }

.live-pill { display:flex; align-items:center; gap:7px; background:#0f172a; color:white; padding:7px 14px; border-radius:99px; font-size:12px; font-weight:700; letter-spacing:0.5px; }
.live-dot  { width:8px; height:8px; background:#ef4444; border-radius:50%; animation:blink 1s infinite; }
.offline-pill { display:flex; align-items:center; gap:7px; background:#64748b; color:white; padding:7px 14px; border-radius:99px; font-size:12px; font-weight:700; }

@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.2;} }

.mini-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
.mini-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:14px 16px; box-shadow:var(--shadow); display:flex; align-items:center; gap:12px; transition:all 0.3s ease; }
.mini-card .mc-icon   { font-size:24px; flex-shrink:0; }
.mini-card .mc-label  { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.8px; color:var(--abu); margin-bottom:2px; }
.mini-card .mc-val    { font-size:20px; font-weight:700; color:var(--teks); font-family:'JetBrains Mono',monospace; line-height:1; }

.mc-status-hama    { background:linear-gradient(135deg,#fef2f2,#fee2e2) !important; border-color:#fca5a5 !important; }
.mc-status-waspada { background:linear-gradient(135deg,#fffbeb,#fef9c3) !important; border-color:#fde68a !important; }
.mc-status-aman    { background:linear-gradient(135deg,#f0fdf4,#dcfce7) !important; border-color:#86efac !important; }
.mc-status-offline { background:linear-gradient(135deg,#f8fafc,#f1f5f9) !important; border-color:#cbd5e1 !important; }

.kamera-grid { display:grid; grid-template-columns:3fr 2fr; gap:18px; margin-bottom:18px; }
.panel { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
.panel-header { padding:14px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.panel-title  { font-size:14px; font-weight:700; color:var(--teks); display:flex; align-items:center; gap:8px; }
.panel-body   { padding:20px; }

.kamera-box { position:relative; background:#0a0a0a; border-radius:10px; overflow:hidden; aspect-ratio:16/9; display:flex; align-items:center; justify-content:center; }
.kamera-box img { width:100%; height:100%; object-fit:cover; display:block; }

.cam-overlay { position:absolute; bottom:0; left:0; right:0; background:linear-gradient(transparent,rgba(0,0,0,0.75)); padding:20px 14px 10px; display:flex; align-items:flex-end; justify-content:space-between; z-index:10; }
.cam-timestamp { color:rgba(255,255,255,0.85); font-size:11px; font-family:'JetBrains Mono',monospace; }
.cam-status-chip { padding:4px 10px; border-radius:99px; font-size:11px; font-weight:700; }

.chip-hama    { background:#dc2626; color:white; }
.chip-waspada { background:#d97706; color:white; }
.chip-aman    { background:#16a34a; color:white; }
.chip-offline { background:#64748b; color:white; }

.badge-live {
    position:absolute; top:12px; left:12px;
    background:#dc2626; color:white; font-size:10px; font-weight:700;
    letter-spacing:1px; padding:3px 9px; border-radius:4px;
    display:flex; align-items:center; gap:5px; z-index:10;
}

.cam-placeholder { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; color:rgba(255,255,255,0.4); font-size:13px; height:100%; width:100%; }
.cam-placeholder .ph-icon { font-size:48px; opacity:0.4; }

.refresh-info { display:flex; align-items:center; gap:6px; margin-top:10px; font-size:12px; color:var(--abu); }
.refresh-spin { width:12px; height:12px; border:2px solid #d1d5db; border-top-color:var(--hijau); border-radius:50%; animation:spin 1.5s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

.status-besar { text-align:center; padding:20px 16px; border-radius:12px; margin-bottom:16px; transition:all 0.3s ease; }
.status-besar.hama    { background:linear-gradient(135deg,#fef2f2,#fee2e2); border:1px solid #fca5a5; }
.status-besar.waspada { background:linear-gradient(135deg,#fffbeb,#fef9c3); border:1px solid #fde68a; }
.status-besar.aman    { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1px solid #86efac; }
.status-besar.offline { background:linear-gradient(135deg,#f8fafc,#f1f5f9); border:1px solid #cbd5e1; }

.status-besar .sb-icon    { font-size:48px; display:block; margin-bottom:10px; }
.status-besar .sb-label   { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px; color:var(--abu); margin-bottom:4px; }
.status-besar .sb-val     { font-size:24px; font-weight:800; line-height:1; margin-bottom:8px; }
.sb-val.hama    { color:#dc2626; }
.sb-val.waspada { color:#d97706; }
.sb-val.aman    { color:#16a34a; }
.sb-val.offline { color:#64748b; }
.status-besar .sb-fuzzy   { font-family:'JetBrains Mono',monospace; font-size:13px; color:var(--abu); background:rgba(255,255,255,0.6); display:inline-block; padding:3px 10px; border-radius:99px; margin-bottom:10px; }
.status-besar .sb-desc    { font-size:13px; line-height:1.6; color:var(--teks2); }

.info-row         { display:flex; align-items:center; justify-content:space-between; padding:9px 0; border-bottom:1px solid #f8fafc; font-size:13px; }
.info-row:last-child { border-bottom:none; }
.info-label { color:var(--abu); font-weight:500; }
.info-val   { font-weight:700; color:var(--teks); font-family:'JetBrains Mono',monospace; }

.aksi-list { list-style:none; padding:0; margin:0; }
.aksi-list li { display:flex; align-items:flex-start; gap:10px; padding:8px 0; font-size:13px; color:var(--teks2); border-bottom:1px solid #f8fafc; line-height:1.5; }
.aksi-list li:last-child { border-bottom:none; }
.aksi-list .aksi-num { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; margin-top:1px; }

.num-hama    { background:#fee2e2; color:#dc2626; }
.num-waspada { background:#fef9c3; color:#d97706; }
.num-aman    { background:#dcfce7; color:#16a34a; }

.foto-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; }
.foto-item { position:relative; border-radius:8px; overflow:hidden; aspect-ratio:4/3; cursor:pointer; border:2px solid transparent; transition:border-color 0.2s,transform 0.2s; }
.foto-item:hover { border-color:var(--hijau); transform:scale(1.02); }
.foto-item img { width:100%; height:100%; object-fit:cover; }
.foto-item .foto-badge { position:absolute; bottom:4px; left:50%; transform:translateX(-50%); padding:2px 7px; border-radius:4px; font-size:10px; font-weight:700; white-space:nowrap; }

.foto-placeholder { background:#f8fafc; border:2px dashed #e2e8f0; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; color:var(--abu); padding:16px; grid-column:1/-1; }

@media (max-width:900px) {
    .mini-grid   { grid-template-columns:repeat(2,1fr); }
    .kamera-grid { grid-template-columns:1fr; }
}

@media (max-width:560px) {
    .mini-grid   { grid-template-columns:1fr !important; }
    .foto-grid   { grid-template-columns:repeat(2,1fr); }
}

</style>

<div class="page-header">
    <div>
        <h1>📷 Monitoring Visual Tanaman Jagung</h1>
        <p>Pemantauan kamera lapangan secara real-time disertai analisis deteksi hama berbasis Fuzzy Sugeno</p>
    </div>
    <div id="header-pill">
        @if($isOnline)
            <div class="live-pill"><span class="live-dot"></span> LIVE MONITORING</div>
        @else
            <div class="offline-pill">📡 ALAT OFFLINE</div>
        @endif
    </div>
</div>

<div class="mini-grid">
    <div class="mini-card">
        <span class="mc-icon">🌡️</span>
        <div class="mc-info">
            <div class="mc-label">Suhu Udara</div>
            <div class="mc-val">
                <span id="mini-suhu">{{ $isOnline ? ($latest->suhu ?? '--') : '--' }}</span>
                <span style="font-size:13px;">°C</span>
            </div>
        </div>
    </div>

    <div class="mini-card">
        <span class="mc-icon">💧</span>
        <div class="mc-info">
            <div class="mc-label">Kel. Udara</div>
            <div class="mc-val">
                <span id="mini-kel-udara">{{ $isOnline ? ($latest->kelembapan_udara ?? '--') : '--' }}</span>
                <span style="font-size:13px;">%</span>
            </div>
        </div>
    </div>

    <div class="mini-card">
        <span class="mc-icon">🌱</span>
        <div class="mc-info">
            <div class="mc-label">Kel. Tanah</div>
            <div class="mc-val">
                <span id="mini-kel-tanah">{{ $isOnline ? ($latest->kelembapan_tanah ?? '--') : '--' }}</span>
                <span style="font-size:13px;">%</span>
            </div>
        </div>
    </div>

    <div id="mini-card-status" class="mini-card
        @if(!$isOnline) mc-status-offline
        @elseif($status=='HAMA') mc-status-hama
        @elseif($status=='WASPADA') mc-status-waspada
        @else mc-status-aman
        @endif">
        <span class="mc-icon" id="mini-icon-status">
            @if(!$isOnline) 📡 @elseif($status=='HAMA') 🚨 @elseif($status=='WASPADA') ⚠️ @else ✅ @endif
        </span>
        <div class="mc-info">
            <div class="mc-label">Status Hama</div>
            <div class="mc-val" style="font-size:16px;" id="mini-val-status">
                {{ $isOnline ? $status : 'OFFLINE' }}
            </div>
        </div>
    </div>
</div>

<style>
.kamera-layout { display: grid; grid-template-columns: 3fr 2fr; gap: 18px; align-items: start; margin-bottom: 18px; }
.kamera-main-panel { grid-column: 1; grid-row: 1; margin-bottom: 0 !important; }
.kamera-side-panel { grid-column: 2; grid-row: 1 / span 2; margin-bottom: 0 !important; }
.kamera-riwayat-panel { grid-column: 1; grid-row: 2; margin-bottom: 0 !important; }
@media (max-width: 900px) {
    .kamera-layout { display: flex; flex-direction: column; gap: 16px; }
    .kamera-main-panel, .kamera-side-panel, .kamera-riwayat-panel { grid-column: auto; grid-row: auto; margin-bottom: 0 !important; }
}
</style>

<div class="kamera-layout">

    <div class="panel kamera-main-panel">
        <div class="panel-header">
            <div class="panel-title">📸 Kamera Lapangan — Tanaman Jagung</div>
            <span style="font-size:11px; color:var(--abu); font-family:'JetBrains Mono',monospace;">
                Refresh otomatis / 5 detik
            </span>
        </div>
        <div class="panel-body">

            <div class="kamera-box" id="camBox">
                <div class="badge-live" id="badge-live" style="{{ $isOnline ? '' : 'display:none;' }}">
                    <span style="width:6px;height:6px;background:white;border-radius:50%;display:inline-block;animation:blink 1s infinite;"></span>
                    LIVE
                </div>

                <div id="cam-content-wrapper" style="width:100%;height:100%;">
                    <div class="cam-placeholder">
                        <span class="ph-icon">📷</span>
                        <span>{{ $isOnline ? 'Memuat gambar dari kamera IoT...' : 'Alat IoT tidak terhubung' }}</span>
                        <span style="font-size:11px; opacity:0.6;">Gambar akan tampil saat sensor mengirim data</span>
                    </div>
                </div>
            </div>

            <div class="refresh-info">
                <div class="refresh-spin"></div>
                <span>Data sensor diperbarui otomatis setiap 5 detik dari perangkat IoT ESP32-CAM</span>
            </div>

            <!-- INFORMASI STATUS KAMERA -->
            <div style="margin-top: 16px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; color: #475569; display: flex; gap: 10px; align-items: flex-start; line-height: 1.5;">
                <span style="font-size: 18px; display: inline-block; margin-top: 2px;">ℹ️</span>
                <div>
                    <b>Catatan Monitoring:</b> Gambar yang ditampilkan merupakan bukti visual <b>terakhir</b> yang direkam saat terjadi deteksi hama. Sementara itu, indikator <b>Status Deteksi Visual YOLO</b> dan <b>Keputusan Sistem</b> di panel samping selalu menunjukkan kondisi lapangan secara aktual (<i>real-time</i>).
                </div>
            </div>

        </div>
    </div>

    <div class="kamera-side-panel" style="display:flex; flex-direction:column; gap:16px;">

        <!-- ================== PANEL HASIL DETEKSI ================== -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">🧮 Hasil Analisis Sensor</div>
            </div>
            <div class="panel-body">

                <!-- DECISION RULE BREAKDOWN -->
                <div class="decision-rule-container" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:16px;">
                    <div style="font-size:12px; font-weight:700; color:var(--abu); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.5px;">Evaluasi Decision Rule</div>
                    
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <!-- Prediksi Sensor -->
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:white; border-radius:8px; border:1px solid #f1f5f9;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:18px;">🧮</span>
                                <div>
                                    <div style="font-size:12px; font-weight:600; color:var(--teks);">Prediksi Sensor (Fuzzy)</div>
                                    <div style="font-size:11px; color:var(--abu);">Skor Fuzzy: <span id="dr-fuzzy-skor" style="font-family:'JetBrains Mono',monospace; font-weight:600;">{{ $isOnline ? round($latest->nilai_fuzzy ?? 0, 4) : '--' }}</span></div>
                                </div>
                            </div>
                            <span id="dr-prediksi-badge" style="font-size:11px; font-weight:700; padding:4px 10px; border-radius:99px; background:#e2e8f0; color:#64748b;">--</span>
                        </div>

                        <!-- Deteksi YOLO -->
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:white; border-radius:8px; border:1px solid #f1f5f9;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:18px;">🎯</span>
                                <div>
                                    <div style="font-size:12px; font-weight:600; color:var(--teks);">Deteksi Visual YOLO</div>
                                    <div style="font-size:11px; color:var(--abu);">Confidence: <span id="dr-yolo-conf" style="font-family:'JetBrains Mono',monospace; font-weight:600;">{{ $isOnline ? ($latest->confidence_yolo ?? '--') : '--' }}</span></div>
                                </div>
                            </div>
                            <span id="dr-yolo-badge" style="font-size:11px; font-weight:700; padding:4px 10px; border-radius:99px; background:#e2e8f0; color:#64748b;">--</span>
                        </div>
                    </div>
                </div>

                <!-- STATUS BESAR -->
                <div id="panel-status-besar" class="status-besar
                    @if(!$isOnline) offline
                    @elseif($status=='HAMA') hama
                    @elseif($status=='WASPADA') waspada
                    @else aman
                    @endif">
                    <span class="sb-icon" id="sb-icon">
                        @if(!$isOnline) 📡 @elseif($status=='HAMA') 🚨 @elseif($status=='WASPADA') ⚠️ @else 🌿 @endif
                    </span>
                    <div class="sb-label">Keputusan Sistem Akhir</div>
                    <div id="sb-val" class="sb-val
                        @if(!$isOnline) offline
                        @elseif($status=='HAMA') hama
                        @elseif($status=='WASPADA') waspada
                        @else aman
                        @endif">
                        @if(!$isOnline) ALAT OFFLINE
                        @elseif($status=='HAMA') HAMA TERDETEKSI
                        @elseif($status=='WASPADA') PERLU WASPADA
                        @else TANAMAN AMAN
                        @endif
                    </div>
                    <div class="sb-desc" id="sb-desc">
                        @if(!$isOnline)
                            Perangkat IoT sedang tidak terhubung. Periksa koneksi jaringan ESP32.
                        @elseif($status=='HAMA')
                            Berdasarkan Keputusan Sistem (Fuzzy + YOLO). Kondisi SANGAT RAWAN HAMA! Segera lakukan tindakan pengendalian.
                        @elseif($status=='WASPADA')
                            Berdasarkan Prediksi Sensor (Fuzzy). Kondisi mulai rawan. Tingkatkan monitoring.
                        @else
                            Berdasarkan Prediksi Sensor (Fuzzy) dan Visual YOLO. Kondisi aman. Lanjutkan pemeliharaan rutin.
                        @endif
                    </div>
                </div>

                <div class="info-row">
                    <span class="info-label">🌡️ Suhu</span>
                    <span class="info-val" id="detail-suhu">{{ $isOnline ? (($latest->suhu ?? '-') . ' °C') : '--' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">💧 Kel. Udara</span>
                    <span class="info-val" id="detail-kel-udara">{{ $isOnline ? (($latest->kelembapan_udara ?? '-') . ' %') : '--' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">🌱 Kel. Tanah</span>
                    <span class="info-val" id="detail-kel-tanah">{{ $isOnline ? (($latest->kelembapan_tanah ?? '-') . ' %') : '--' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">🕐 Terakhir update</span>
                    <span class="info-val" style="font-size:11px;" id="detail-time">
                        {{ $isOnline && $latest ? \Carbon\Carbon::parse($latest->created_at)->format('H:i, d M Y') : '--' }}
                    </span>
                </div>

            </div>
        </div>
        <!-- ================== END PANEL ================== -->

        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">📋 Rekomendasi Tindakan</div>
            </div>
            <div class="panel-body" style="padding:16px 20px;">
                <ul class="aksi-list" id="rekomendasi-list">
                    @if(!$isOnline)
                        <li><span class="aksi-num num-aman">!</span> Periksa koneksi jaringan perangkat IoT.</li>
                        <li><span class="aksi-num num-aman">!</span> Pastikan ESP32 menyala dan terhubung WiFi.</li>
                        <li><span class="aksi-num num-aman">!</span> Pantau kembali setelah perangkat online.</li>
                    @else
                        @php
                            if ($status === 'HAMA') {
                                $actions = [
                                    'Hentikan kegiatan penyiraman berlebih untuk mengurangi kelembapan.',
                                    'Lakukan pemeriksaan fisik daun dan batang tanaman jagung.',
                                    'Aplikasikan pestisida atau agen hayati sesuai jenis hama.',
                                    'Catat temuan dan laporkan ke petugas pertanian setempat.',
                                    'Pantau sensor setiap jam hingga nilai fuzzy menurun di bawah 0.70.',
                                ];
                            } elseif ($status === 'WASPADA') {
                                $actions = [
                                    'Tingkatkan frekuensi pemantauan menjadi setiap 2–3 jam.',
                                    'Periksa bagian bawah daun untuk tanda awal kehadiran hama.',
                                    'Pastikan drainase lahan baik untuk menurunkan kelembapan tanah.',
                                    'Siapkan agen pengendalian hama jika status meningkat.',
                                ];
                            } else {
                                $actions = [
                                    'Lanjutkan pemantauan rutin sesuai jadwal normal.',
                                    'Pastikan sensor IoT berfungsi dan terhubung dengan baik.',
                                    'Catat data historis untuk keperluan analisis jangka panjang.',
                                    'Pertahankan kondisi irigasi dan pemupukan yang sudah berjalan.',
                                ];
                            }
                        @endphp
                        @foreach($actions as $i => $aksi)
                            <li>
                                <span class="aksi-num
                                    @if($status=='HAMA') num-hama
                                    @elseif($status=='WASPADA') num-waspada
                                    @else num-aman
                                    @endif">{{ $i+1 }}</span>
                                {{ $aksi }}
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>

    </div>

    <div class="panel kamera-riwayat-panel">
    <div class="panel-header">
        <div class="panel-title">🖼️ Riwayat Foto Kamera (5 Terakhir)</div>
        <a href="{{ route('riwayat') }}" style="font-size:12px; color:var(--hijau); text-decoration:none; font-weight:600;">
            Lihat Semua →
        </a>
    </div>
    <div class="panel-body" id="riwayat-foto-wrapper">
        <div class="foto-placeholder">📷 Memuat riwayat foto...</div>
    </div>
</div>
</div>

<script>
function fetchLatestCameraData() {
    fetch("{{ route('kamera.api') }}")
        .then(function(r) { return r.json(); })
        .then(function(data) {

            if (!data.success || !data.isOnline) {
                setKameraOffline();
                return;
            }

            var badgeLive = document.getElementById('badge-live');
            if (badgeLive) badgeLive.style.display = 'flex';

            var headerPill = document.getElementById('header-pill');
            if (headerPill) headerPill.innerHTML =
                '<div class="live-pill"><span class="live-dot"></span> LIVE MONITORING</div>';

            elSet('mini-suhu',        data.suhu);
            elSet('mini-kel-udara',   data.kelembapan_udara);
            elSet('mini-kel-tanah',   data.kelembapan_tanah);
            elSet('detail-suhu',      data.suhu + ' °C');
            elSet('detail-kel-udara', data.kelembapan_udara + ' %');
            elSet('detail-kel-tanah', data.kelembapan_tanah + ' %');
            elSet('detail-time',      data.formatted_time);

            var miniCard = document.getElementById('mini-card-status');
            var miniIcon = document.getElementById('mini-icon-status');
            var miniVal  = document.getElementById('mini-val-status');
            if (miniVal) miniVal.innerText = data.status;
            if (miniCard) {
                miniCard.className = 'mini-card';
                if      (data.status === 'HAMA')    miniCard.classList.add('mc-status-hama');
                else if (data.status === 'WASPADA') miniCard.classList.add('mc-status-waspada');
                else                                miniCard.classList.add('mc-status-aman');
            }
            if (miniIcon) {
                if      (data.status === 'HAMA')    miniIcon.innerText = '🚨';
                else if (data.status === 'WASPADA') miniIcon.innerText = '⚠️';
                else                                miniIcon.innerText = '✅';
            }

            var cw = document.getElementById('cam-content-wrapper');
            if (cw) {
                if (data.image) {
                    var chipClass = data.status === 'HAMA' ? 'chip-hama'
                                 : (data.status === 'WASPADA' ? 'chip-waspada' : 'chip-aman');
                    var imageUrl = data.image + '?t=' + Date.now();
                    cw.innerHTML =
                        '<img src="' + imageUrl + '" alt="Gambar tanaman jagung dari kamera IoT">' +
                        '<div class="cam-overlay">' +
                        '  <span class="cam-timestamp">' + data.formatted_timestamp + '</span>' +
                        '  <span class="cam-status-chip ' + chipClass + '">' + data.status + '</span>' +
                        '</div>';
                } else {
                    cw.innerHTML =
                        '<div class="cam-placeholder">' +
                        '  <span class="ph-icon">📷</span>' +
                        '  <span>Belum ada gambar dari kamera IoT</span>' +
                        '  <span style="font-size:11px;opacity:0.6;">Gambar akan tampil saat ESP32-CAM mengirim foto</span>' +
                        '</div>';
                }
            }

            // Update Decision Rule Breakdown
            var fuzzyVal = parseFloat(data.nilai) || 0;
            elSet('dr-fuzzy-skor', fuzzyVal.toFixed(4));
            
            var predBadge = document.getElementById('dr-prediksi-badge');
            if (predBadge) {
                predBadge.innerText = data.prediksi_sensor || 'AMAN';
                if (data.prediksi_sensor === 'HAMA') {
                    predBadge.style.background = '#fee2e2'; predBadge.style.color = '#dc2626';
                } else if (data.prediksi_sensor === 'WASPADA') {
                    predBadge.style.background = '#fef9c3'; predBadge.style.color = '#d97706';
                } else {
                    predBadge.style.background = '#dcfce7'; predBadge.style.color = '#16a34a';
                }
            }

            var yoloConf = data.confidence_yolo ? (parseFloat(data.confidence_yolo) * 100).toFixed(0) + '%' : '--';
            elSet('dr-yolo-conf', yoloConf);
            
            var yoloBadge = document.getElementById('dr-yolo-badge');
            if (yoloBadge) {
                var yStatus = data.hasil_deteksi_yolo || 'OFF';
                if (yStatus === 'ON') {
                    yoloBadge.innerText = 'ON - TIKUS TERDETEKSI';
                    yoloBadge.style.background = '#fee2e2'; yoloBadge.style.color = '#dc2626';
                } else {
                    yoloBadge.innerText = 'OFF - TIDAK ADA TIKUS';
                    yoloBadge.style.background = '#dcfce7'; yoloBadge.style.color = '#16a34a';
                }
            }

            // Update status display (Keputusan Sistem)
            var status = data.keputusan_sistem || data.status;

            // Update status besar
            var panelBesar = document.getElementById('panel-status-besar');
            var sbIcon     = document.getElementById('sb-icon');
            var sbVal      = document.getElementById('sb-val');
            var sbDesc     = document.getElementById('sb-desc');

            if (panelBesar) panelBesar.className = 'status-besar';
            if (sbVal)      sbVal.className      = 'sb-val';

            if (status === 'HAMA') {
                if (panelBesar) panelBesar.classList.add('hama');
                if (sbVal)  { sbVal.classList.add('hama');    sbVal.innerText = 'HAMA TERDETEKSI'; }
                if (sbIcon)   sbIcon.innerText = '🚨';
                if (sbDesc)   sbDesc.innerText = 'Berdasarkan Keputusan Sistem (Fuzzy + YOLO). Kondisi SANGAT RAWAN HAMA! Segera lakukan tindakan pengendalian.';
            } else if (status === 'WASPADA') {
                if (panelBesar) panelBesar.classList.add('waspada');
                if (sbVal)  { sbVal.classList.add('waspada'); sbVal.innerText = 'PERLU WASPADA'; }
                if (sbIcon)   sbIcon.innerText = '⚠️';
                if (sbDesc)   sbDesc.innerText = 'Berdasarkan Prediksi Sensor (Fuzzy). Kondisi mulai rawan. Tingkatkan monitoring.';
            } else {
                if (panelBesar) panelBesar.classList.add('aman');
                if (sbVal)  { sbVal.classList.add('aman');    sbVal.innerText = 'TANAMAN AMAN'; }
                if (sbIcon)   sbIcon.innerText = '🌿';
                if (sbDesc)   sbDesc.innerText = 'Berdasarkan Prediksi Sensor (Fuzzy) dan Visual YOLO. Kondisi aman. Lanjutkan pemeliharaan rutin.';
            }

            var rekList = document.getElementById('rekomendasi-list');
            if (rekList && data.rekomendasi) {
                rekList.innerHTML = '';
                var numClass = status === 'HAMA' ? 'num-hama'
                             : (status === 'WASPADA' ? 'num-waspada' : 'num-aman');
                data.rekomendasi.forEach(function(aksi, idx) {
                    var li = document.createElement('li');
                    li.innerHTML = '<span class="aksi-num ' + numClass + '">' + (idx + 1) + '</span> ' + aksi;
                    rekList.appendChild(li);
                });
            }

            var riwayatWrapper = document.getElementById('riwayat-foto-wrapper');
            if (riwayatWrapper && data.riwayat_html) {
                riwayatWrapper.innerHTML = data.riwayat_html;
            }
        })
        .catch(function(err) {
            console.error('Gagal memperbarui data monitor kamera:', err);
        });
}

function setKameraOffline() {
    var badgeLive = document.getElementById('badge-live');
    if (badgeLive) badgeLive.style.display = 'none';

    var headerPill = document.getElementById('header-pill');
    if (headerPill) headerPill.innerHTML = '<div class="offline-pill">📡 ALAT OFFLINE</div>';

    ['mini-suhu','mini-kel-udara','mini-kel-tanah'].forEach(function(id) {
        var e = document.getElementById(id); if (e) e.innerText = '--';
    });
    ['detail-suhu','detail-kel-udara','detail-kel-tanah','detail-time','dr-fuzzy-skor','dr-yolo-conf'].forEach(function(id) {
        var e = document.getElementById(id); if (e) e.innerText = '--';
    });
    
    var predBadge = document.getElementById('dr-prediksi-badge');
    if (predBadge) { predBadge.innerText = 'OFFLINE'; predBadge.style.background = '#e2e8f0'; predBadge.style.color = '#64748b'; }
    
    var yoloBadge = document.getElementById('dr-yolo-badge');
    if (yoloBadge) { yoloBadge.innerText = 'OFFLINE'; yoloBadge.style.background = '#e2e8f0'; yoloBadge.style.color = '#64748b'; }

    var miniCard = document.getElementById('mini-card-status');
    var miniIcon = document.getElementById('mini-icon-status');
    var miniVal  = document.getElementById('mini-val-status');
    if (miniCard) miniCard.className = 'mini-card mc-status-offline';
    if (miniIcon) miniIcon.innerText = '📡';
    if (miniVal)  miniVal.innerText  = 'OFFLINE';

    var cw = document.getElementById('cam-content-wrapper');
    if (cw) cw.innerHTML =
        '<div class="cam-placeholder">' +
        '  <span class="ph-icon">📡</span>' +
        '  <span>Alat IoT Tidak Terhubung</span>' +
        '  <span style="font-size:11px;opacity:0.6;">Gambar akan tampil saat perangkat kembali online</span>' +
        '</div>';

    var panelBesar = document.getElementById('panel-status-besar');
    var sbIcon     = document.getElementById('sb-icon');
    var sbVal      = document.getElementById('sb-val');
    var sbDesc     = document.getElementById('sb-desc');
    if (panelBesar) panelBesar.className = 'status-besar offline';
    if (sbIcon)     sbIcon.innerText     = '📡';
    if (sbVal)      { sbVal.className    = 'sb-val offline'; sbVal.innerText = 'ALAT OFFLINE'; }
    if (sbDesc)     sbDesc.innerText     = 'Perangkat IoT sedang tidak terhubung. Periksa koneksi jaringan ESP32.';

    var rekList = document.getElementById('rekomendasi-list');
    if (rekList) rekList.innerHTML =
        '<li><span class="aksi-num num-aman">!</span> Periksa koneksi jaringan perangkat IoT.</li>' +
        '<li><span class="aksi-num num-aman">!</span> Pastikan ESP32 menyala dan terhubung WiFi.</li>' +
        '<li><span class="aksi-num num-aman">!</span> Pantau kembali setelah perangkat online.</li>';
}

function elSet(id, val) {
    var e = document.getElementById(id);
    if (e) e.innerText = val;
}

fetchLatestCameraData();
setInterval(fetchLatestCameraData, 5000);
</script>

@endsection