@extends('layouts.app')

@section('title','Monitoring Kamera')

@section('content')

{{-- ============================================================
     HALAMAN KAMERA — MONITORING VISUAL TANAMAN JAGUNG
     Menampilkan gambar terbaru dari kamera IoT beserta
     status deteksi hama berdasarkan Fuzzy Sugeno
     ============================================================ --}}

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

/* ── HEADER ──────────────────────────────────────────────── */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.page-header h1 { font-size: 22px; font-weight: 700; color: var(--teks); margin-bottom: 2px; }
.page-header p  { font-size: 13px; color: var(--abu); }

.live-pill {
    display: flex;
    align-items: center;
    gap: 7px;
    background: #0f172a;
    color: white;
    padding: 7px 14px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.live-dot {
    width: 8px; height: 8px;
    background: #ef4444;
    border-radius: 50%;
    animation: blink 1s infinite;
}

@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.2;} }

/* ── MINI KARTU SENSOR ───────────────────────────────────── */
.mini-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.mini-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 14px 16px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 12px;
}

.mini-card .mc-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.mini-card .mc-info {}

.mini-card .mc-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--abu);
    margin-bottom: 2px;
}

.mini-card .mc-val {
    font-size: 20px;
    font-weight: 700;
    color: var(--teks);
    font-family: 'JetBrains Mono', monospace;
    line-height: 1;
}

/* Status mini card */
.mini-card.mc-status-hama    { background:linear-gradient(135deg,#fef2f2,#fee2e2); border-color:#fca5a5; }
.mini-card.mc-status-waspada { background:linear-gradient(135deg,#fffbeb,#fef9c3); border-color:#fde68a; }
.mini-card.mc-status-aman    { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border-color:#86efac; }

/* ── LAYOUT UTAMA ────────────────────────────────────────── */
.kamera-grid {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 18px;
    margin-bottom: 18px;
}

/* ── PANEL ───────────────────────────────────────────────── */
.panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.panel-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.panel-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--teks);
    display: flex;
    align-items: center;
    gap: 8px;
}

.panel-body { padding: 20px; }

/* ── KAMERA BOX ──────────────────────────────────────────── */
.kamera-box {
    position: relative;
    background: #0a0a0a;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 16/9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.kamera-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Overlay info di pojok gambar */
.cam-overlay {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.75));
    padding: 20px 14px 10px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
}

.cam-timestamp {
    color: rgba(255,255,255,0.85);
    font-size: 11px;
    font-family: 'JetBrains Mono', monospace;
}

.cam-status-chip {
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
}

.chip-hama    { background: #dc2626; color: white; }
.chip-waspada { background: #d97706; color: white; }
.chip-aman    { background: #16a34a; color: white; }

/* Badge LIVE */
.badge-live {
    position: absolute;
    top: 12px; left: 12px;
    background: #dc2626;
    color: white;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    padding: 3px 9px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Placeholder jika tidak ada gambar */
.cam-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: rgba(255,255,255,0.4);
    font-size: 13px;
    height: 100%;
    width: 100%;
}

.cam-placeholder .ph-icon { font-size: 48px; opacity: 0.4; }

/* Refresh info */
.refresh-info {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    font-size: 12px;
    color: var(--abu);
}

.refresh-spin {
    width: 12px; height: 12px;
    border: 2px solid #d1d5db;
    border-top-color: var(--hijau);
    border-radius: 50%;
    animation: spin 1.5s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── PANEL STATUS KANAN ──────────────────────────────────── */
.status-besar {
    text-align: center;
    padding: 20px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
}

.status-besar.hama    { background:linear-gradient(135deg,#fef2f2,#fee2e2); border:1px solid #fca5a5; }
.status-besar.waspada { background:linear-gradient(135deg,#fffbeb,#fef9c3); border:1px solid #fde68a; }
.status-besar.aman    { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1px solid #86efac; }

.status-besar .sb-icon { font-size: 48px; display: block; margin-bottom: 10px; }

.status-besar .sb-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--abu);
    margin-bottom: 4px;
}

.status-besar .sb-val {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 8px;
}

.sb-val.hama    { color: #dc2626; }
.sb-val.waspada { color: #d97706; }
.sb-val.aman    { color: #16a34a; }

.status-besar .sb-fuzzy {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    color: var(--abu);
    background: rgba(255,255,255,0.6);
    display: inline-block;
    padding: 3px 10px;
    border-radius: 99px;
    margin-bottom: 10px;
}

.status-besar .sb-desc {
    font-size: 13px;
    line-height: 1.6;
    color: var(--teks2);
}

/* Divider info baris */
.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 0;
    border-bottom: 1px solid #f8fafc;
    font-size: 13px;
}

.info-row:last-child { border-bottom: none; }
.info-label { color: var(--abu); font-weight: 500; }
.info-val   { font-weight: 700; color: var(--teks); font-family: 'JetBrains Mono', monospace; }

/* Rekomendasi tindakan */
.aksi-list { list-style: none; padding: 0; margin: 0; }

.aksi-list li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 0;
    font-size: 13px;
    color: var(--teks2);
    border-bottom: 1px solid #f8fafc;
    line-height: 1.5;
}

.aksi-list li:last-child { border-bottom: none; }
.aksi-list .aksi-num {
    width: 22px; height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 1px;
}

.num-hama    { background: #fee2e2; color: #dc2626; }
.num-waspada { background: #fef9c3; color: #d97706; }
.num-aman    { background: #dcfce7; color: #16a34a; }

/* ── RIWAYAT FOTO ────────────────────────────────────────── */
.foto-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
}

.foto-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 4/3;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color 0.2s, transform 0.2s;
}

.foto-item:hover {
    border-color: var(--hijau);
    transform: scale(1.02);
}

.foto-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.foto-item .foto-badge {
    position: absolute;
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.foto-placeholder {
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: var(--abu);
    padding: 16px;
    grid-column: 1 / -1;
}

/* ── RESPONSIVE ──────────────────────────────────────────── */
@media (max-width: 900px) {
    .mini-grid  { grid-template-columns: repeat(2, 1fr); }
    .kamera-grid{ grid-template-columns: 1fr; }
}
</style>

{{-- ── HEADER ──────────────────────────────────────────────────── --}}
<div class="page-header">
    <div>
        <h1>📷 Monitoring Visual Tanaman Jagung</h1>
        <p>Pemantauan kamera lapangan secara real-time disertai analisis deteksi hama berbasis Fuzzy Sugeno</p>
    </div>
    <div class="live-pill">
        <span class="live-dot"></span> LIVE MONITORING
    </div>
</div>

{{-- ── MINI KARTU SENSOR ─────────────────────────────────────────── --}}
<div class="mini-grid">

    <div class="mini-card">
        <span class="mc-icon">🌡️</span>
        <div class="mc-info">
            <div class="mc-label">Suhu Udara</div>
            <div class="mc-val">{{ $latest->suhu ?? '--' }}<span style="font-size:13px;">°C</span></div>
        </div>
    </div>

    <div class="mini-card">
        <span class="mc-icon">💧</span>
        <div class="mc-info">
            <div class="mc-label">Kel. Udara</div>
            <div class="mc-val">{{ $latest->kelembapan_udara ?? '--' }}<span style="font-size:13px;">%</span></div>
        </div>
    </div>

    <div class="mini-card">
        <span class="mc-icon">🌱</span>
        <div class="mc-info">
            <div class="mc-label">Kel. Tanah</div>
            <div class="mc-val">{{ $latest->kelembapan_tanah ?? '--' }}<span style="font-size:13px;">%</span></div>
        </div>
    </div>

    <div class="mini-card
        @if($status=='HAMA') mc-status-hama
        @elseif($status=='WASPADA') mc-status-waspada
        @else mc-status-aman
        @endif">
        <span class="mc-icon">
            @if($status=='HAMA') 🚨 @elseif($status=='WASPADA') ⚠️ @else ✅ @endif
        </span>
        <div class="mc-info">
            <div class="mc-label">Status Hama</div>
            <div class="mc-val" style="font-size:16px;">{{ $status }}</div>
        </div>
    </div>

</div>

{{-- ── GRID KAMERA + STATUS ─────────────────────────────────────── --}}
<div class="kamera-grid">

    {{-- PANEL KAMERA --}}
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">
                📸 Kamera Lapangan — Tanaman Jagung
            </div>
            <span style="font-size:11px; color:var(--abu); font-family:'JetBrains Mono',monospace;">
                Refresh otomatis / 5 detik
            </span>
        </div>
        <div class="panel-body">

            {{-- Kotak kamera --}}
            <div class="kamera-box" id="camBox">

                <div class="badge-live">
                    <span style="width:6px;height:6px;background:white;border-radius:50%;display:inline-block;animation:blink 1s infinite;"></span>
                    LIVE
                </div>

                @if($latest && $latest->image)
                    <img id="kamera"
                         src="{{ asset('storage/'.$latest->image) }}"
                         alt="Gambar tanaman jagung dari kamera IoT">

                    <div class="cam-overlay">
                        <span class="cam-timestamp">
                            {{ $latest->created_at->format('d M Y — H:i:s') }}
                        </span>
                        <span class="cam-status-chip
                            @if($status=='HAMA') chip-hama
                            @elseif($status=='WASPADA') chip-waspada
                            @else chip-aman
                            @endif">
                            {{ $status }}
                        </span>
                    </div>

                @else
                    <div class="cam-placeholder">
                        <span class="ph-icon">📷</span>
                        <span>Belum ada gambar dari kamera IoT</span>
                        <span style="font-size:11px; opacity:0.6;">Gambar akan tampil saat sensor mengirim data</span>
                    </div>
                @endif

            </div>

            {{-- Info refresh --}}
            <div class="refresh-info">
                <div class="refresh-spin"></div>
                <span>Gambar diperbarui otomatis setiap 5 detik dari kamera IoT ESP32-CAM</span>
            </div>

        </div>
    </div>

    {{-- PANEL STATUS DETEKSI --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Status besar --}}
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">🧮 Hasil Deteksi Fuzzy Sugeno</div>
            </div>
            <div class="panel-body">

                <div class="status-besar
                    @if($status=='HAMA') hama
                    @elseif($status=='WASPADA') waspada
                    @else aman
                    @endif">
                    <span class="sb-icon">
                        @if($status=='HAMA') 🚨 @elseif($status=='WASPADA') ⚠️ @else 🌿 @endif
                    </span>
                    <div class="sb-label">Status Deteksi Hama</div>
                    <div class="sb-val
                        @if($status=='HAMA') hama
                        @elseif($status=='WASPADA') waspada
                        @else aman
                        @endif">
                        @if($status=='HAMA') HAMA TERDETEKSI
                        @elseif($status=='WASPADA') PERLU WASPADA
                        @else TANAMAN AMAN
                        @endif
                    </div>
                    <div class="sb-fuzzy">Fuzzy: {{ number_format($nilai, 4) }}</div>
                    <div class="sb-desc">
                        @if($status=='HAMA')
                            Nilai Fuzzy Sugeno ≥ 0.70. Kondisi lingkungan sangat mendukung perkembangan hama pada tanaman jagung.
                        @elseif($status=='WASPADA')
                            Nilai Fuzzy Sugeno 0.45–0.70. Kondisi mulai memungkinkan munculnya hama. Pantau secara intensif.
                        @else
                            Nilai Fuzzy Sugeno &lt; 0.45. Kondisi tidak mendukung perkembangan hama. Pertahankan kondisi saat ini.
                        @endif
                    </div>
                </div>

                {{-- Detail sensor --}}
                <div class="info-row">
                    <span class="info-label">🌡️ Suhu</span>
                    <span class="info-val">{{ $latest->suhu ?? '-' }} °C</span>
                </div>
                <div class="info-row">
                    <span class="info-label">💧 Kel. Udara</span>
                    <span class="info-val">{{ $latest->kelembapan_udara ?? '-' }} %</span>
                </div>
                <div class="info-row">
                    <span class="info-label">🌱 Kel. Tanah</span>
                    <span class="info-val">{{ $latest->kelembapan_tanah ?? '-' }} %</span>
                </div>
                <div class="info-row">
                    <span class="info-label">🕐 Terakhir update</span>
                    <span class="info-val" style="font-size:11px;">
                        {{ $latest ? $latest->created_at->format('H:i, d M Y') : '-' }}
                    </span>
                </div>

            </div>
        </div>

        {{-- Rekomendasi tindakan --}}
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">📋 Rekomendasi Tindakan</div>
            </div>
            <div class="panel-body" style="padding:16px 20px;">
                <ul class="aksi-list">
                    @if($status=='HAMA')
                        @foreach([
                            'Hentikan kegiatan penyiraman berlebih untuk mengurangi kelembapan.',
                            'Lakukan pemeriksaan fisik daun dan batang tanaman jagung.',
                            'Aplikasikan pestisida atau agen hayati sesuai jenis hama.',
                            'Catat temuan dan laporkan ke petugas pertanian setempat.',
                            'Pantau sensor setiap jam hingga nilai fuzzy menurun di bawah 0.70.',
                        ] as $i => $aksi)
                        <li>
                            <span class="aksi-num num-hama">{{ $i+1 }}</span>
                            {{ $aksi }}
                        </li>
                        @endforeach
                    @elseif($status=='WASPADA')
                        @foreach([
                            'Tingkatkan frekuensi pemantauan menjadi setiap 2–3 jam.',
                            'Periksa bagian bawah daun untuk tanda awal kehadiran hama.',
                            'Pastikan drainase lahan baik untuk menurunkan kelembapan tanah.',
                            'Siapkan agen pengendalian hama jika status meningkat.',
                        ] as $i => $aksi)
                        <li>
                            <span class="aksi-num num-waspada">{{ $i+1 }}</span>
                            {{ $aksi }}
                        </li>
                        @endforeach
                    @else
                        @foreach([
                            'Lanjutkan pemantauan rutin sesuai jadwal normal.',
                            'Pastikan sensor IoT berfungsi dan terhubung dengan baik.',
                            'Catat data historis untuk keperluan analisis jangka panjang.',
                            'Pertahankan kondisi irigasi dan pemupukan yang sudah berjalan.',
                        ] as $i => $aksi)
                        <li>
                            <span class="aksi-num num-aman">{{ $i+1 }}</span>
                            {{ $aksi }}
                        </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>

    </div>

</div>

{{-- ── RIWAYAT FOTO DARI KAMERA ─────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <div class="panel-title">🖼️ Riwayat Foto Kamera (5 Terakhir)</div>
        <a href="{{ route('riwayat') }}" style="font-size:12px; color:var(--hijau); text-decoration:none; font-weight:600;">
            Lihat Semua →
        </a>
    </div>
    <div class="panel-body">
        @php
            $fotoData = \App\Models\SensorReading::latest()->take(5)->get();
            $adaFoto  = $fotoData->where('image', '!=', null)->count();
        @endphp

        @if($adaFoto > 0)
        <div class="foto-grid">
            @foreach($fotoData as $fd)
                @if($fd->image)
                @php
                    $fn = round((function() use ($fd) {
                        $s=$fd->suhu;$u=$fd->kelembapan_udara;$t=$fd->kelembapan_tanah;
                        $din=max(0,min(1,(25-$s)/5));$han=max(0,min(1,min(($s-22)/5,(32-$s)/5)));$pan=max(0,min(1,($s-30)/5));
                        $ku=max(0,min(1,(65-$u)/15));$nu=max(0,min(1,min(($u-60)/12,(85-$u)/13)));$lu=max(0,min(1,($u-78)/12));
                        $kt=max(0,min(1,(50-$t)/20));$nt=max(0,min(1,min(($t-40)/20,(80-$t)/20)));$lt=max(0,min(1,($t-65)/20));
                        $rules=[[min($pan,$lu,$lt),1.00],[min($pan,$lu,$nt),0.85],[min($pan,$lu,$kt),0.75],
                            [min($han,$lu,$lt),0.80],[min($han,$lu,$nt),0.65],[min($pan,$nu,$lt),0.70],
                            [min($pan,$nu,$nt),0.55],[min($han,$nu,$nt),0.40],[min($han,$nu,$kt),0.30],
                            [min($din,$ku,$kt),0.10],[min($din,$nu,$nt),0.20],[min($pan,$ku,$kt),0.30],
                            [min($din,$lu,$lt),0.45],[min($han,$ku,$kt),0.20]];
                        $n=0;$de=0;foreach($rules as[$r,$z]){$n+=$r*$z;$de+=$r;}
                        return $de==0?0:$n/$de;
                    })(), 2);
                    $fs = $fn>=0.70?'HAMA':($fn>=0.45?'WASPADA':'AMAN');
                @endphp
                <a href="{{ asset('storage/'.$fd->image) }}" target="_blank" class="foto-item" title="{{ $fd->created_at->format('d M Y H:i') }}">
                    <img src="{{ asset('storage/'.$fd->image) }}" alt="Foto tanaman">
                    <span class="foto-badge
                        @if($fs=='HAMA') chip-hama
                        @elseif($fs=='WASPADA') chip-waspada
                        @else chip-aman
                        @endif">{{ $fs }}</span>
                </a>
                @endif
            @endforeach
        </div>
        @else
        <div class="foto-placeholder">
            📷 Belum ada foto dari kamera IoT. Foto akan muncul secara otomatis saat perangkat mengirim data gambar.
        </div>
        @endif
    </div>
</div>

<script>
// ── AUTO REFRESH GAMBAR KAMERA ──────────────────────────────
setInterval(() => {
    const img = document.getElementById('kamera');
    if (img) {
        const base = img.src.split('?')[0];
        img.src = base + '?t=' + Date.now();
    }
}, 5000);
</script>

@endsection
