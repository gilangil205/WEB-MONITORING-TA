@extends('layouts.app')

@section('title', 'Panel Admin')

@section('content')
<style>
.admin-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg,#7c3aed,#6d28d9);
    color:white; padding:8px 16px; border-radius:99px;
    font-size:12px; font-weight:700;
    box-shadow:0 2px 8px rgba(124,58,237,0.25);
}

/* Stat cards */
.stat-row { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:32px; }
.stat-card { background:white; border:1px solid #e2e8f0; border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.06); display:flex; align-items:center; gap:18px; transition:transform 0.2s,box-shadow 0.2s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(0,0,0,0.1); }
.stat-icon { width:60px; height:60px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:28px; flex-shrink:0; }
.stat-val   { font-size:32px; font-weight:800; color:var(--teks); font-family:var(--mono); line-height:1; }
.stat-label { font-size:12px; color:var(--abu); font-weight:600; margin-top:4px; }

/* Layout dua kolom */
.admin-grid { display:grid; grid-template-columns:1.2fr 1fr; gap:24px; align-items:start; }

/* ── Kartu parameter ── */
.param-card { border:1px solid #e2e8f0; border-radius:14px; padding:24px; margin-bottom:20px; background:white; box-shadow:0 2px 12px rgba(0,0,0,0.04); }
.param-card:last-of-type { margin-bottom:0; }
.param-title { display:flex; align-items:center; gap:12px; font-size:15px; font-weight:700; color:var(--teks); margin-bottom:20px; }
.param-title .pi { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; }

/* 3 kolom input per parameter */
.zone-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:18px; }

/* Header zona dengan warna */
.zone-col { display:flex; flex-direction:column; gap:6px; }
.zone-header { display:flex; align-items:center; gap:6px; padding:6px 10px; border-radius:8px; font-size:11px; font-weight:700; letter-spacing:0.5px; }
.zone-aman    { background:#dcfce7; color:#166534; }
.zone-waspada { background:#fef9c3; color:#713f12; }
.zone-hama    { background:#fee2e2; color:#991b1b; }
.zone-label   { font-size:12px; font-weight:600; color:#475569; }

.zone-input {
    width:100%; padding:10px 12px; border-radius:10px;
    font-size:14px; font-weight:700; font-family:var(--mono); color:var(--teks);
    background:#fafbfc; transition:border-color 0.15s,box-shadow 0.15s,background 0.15s; outline:none;
}
.zone-input.inp-aman    { border:2px solid #86efac; }
.zone-input.inp-aman:focus    { border-color:#16a34a; box-shadow:0 0 0 3px rgba(22,163,74,0.12); background:white; }
.zone-input.inp-waspada { border:2px solid #fde68a; }
.zone-input.inp-waspada:focus { border-color:#d97706; box-shadow:0 0 0 3px rgba(217,119,6,0.12); background:white; }
.zone-input.inp-hama    { border:2px solid #fca5a5; }
.zone-input.inp-hama:focus    { border-color:#dc2626; box-shadow:0 0 0 3px rgba(220,38,38,0.12); background:white; }

.zone-hint { font-size:10px; color:#94a3b8; margin-top:3px; }

/* Bar visual 3 zona */
.bar-3zona { height:10px; border-radius:99px; overflow:hidden; margin:16px 0 6px; position:relative; background:#e2e8f0; }
.bar-aman    { position:absolute; top:0; left:0; height:100%; background:#22c55e; }
.bar-waspada { position:absolute; top:0; height:100%; background:#facc15; }
.bar-hama    { position:absolute; top:0; height:100%; right:0; background:#ef4444; }
.bar-scale { display:flex; justify-content:space-between; font-size:10px; color:#94a3b8; font-family:var(--mono); }
.bar-legend { display:flex; gap:14px; margin-top:6px; justify-content:center; }
.bar-legend-item { display:flex; align-items:center; gap:5px; font-size:10px; color:#64748b; font-weight:600; }
.bar-dot { width:10px; height:10px; border-radius:2px; }

/* Tombol */
.btn-primary {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg,#16a34a,#15803d); color:white;
    border:none; padding:12px 26px; border-radius:10px; cursor:pointer;
    font-size:14px; font-weight:700; font-family:var(--font);
    box-shadow:0 4px 14px rgba(22,163,74,0.3); transition:all 0.2s;
}
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(22,163,74,0.4); }

.btn-secondary {
    display:inline-flex; align-items:center; gap:8px;
    background:white; color:#64748b; border:1.5px solid #e2e8f0;
    padding:11px 22px; border-radius:10px; cursor:pointer;
    font-size:13px; font-weight:600; font-family:var(--font); transition:all 0.15s;
}
.btn-secondary:hover { background:#f8fafc; border-color:#cbd5e1; color:var(--teks); }

.btn-danger {
    display:inline-flex; align-items:center; gap:6px;
    background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5;
    padding:7px 14px; border-radius:8px; cursor:pointer;
    font-size:12px; font-weight:600; font-family:var(--font); transition:all 0.15s;
}
.btn-danger:hover { background:#dc2626; color:white; border-color:#dc2626; }

.btn-green {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg,#16a34a,#15803d); color:white;
    border:none; padding:12px 26px; border-radius:10px; cursor:pointer;
    font-size:14px; font-weight:700; font-family:var(--font);
    box-shadow:0 4px 14px rgba(22,163,74,0.3); transition:all 0.2s;
}
.btn-green:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(22,163,74,0.4); }

/* Form user */
.form-group { margin-bottom:18px; }
.form-group:last-child { margin-bottom:0; }
.form-label { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.form-label span { font-size:13px; font-weight:600; color:#475569; }
.form-input {
    width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:10px;
    font-size:14px; font-weight:600; font-family:var(--mono); color:var(--teks);
    background:#fafbfc; transition:border-color 0.15s,box-shadow 0.15s,background 0.15s; outline:none;
}
.form-input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,0.1); background:white; }
.form-input::placeholder { color:#cbd5e1; }
.password-wrap { position:relative; }
.password-wrap .form-input { padding-right:42px; }
.toggle-pw { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:0; transition:color 0.2s; }
.toggle-pw:hover { color:#64748b; }
.field-error { font-size:12px; color:#dc2626; margin-top:5px; font-weight:500; }

.role-badge { display:inline-block; padding:4px 12px; border-radius:10px; font-size:12px; font-weight:700; }
.role-admin { background:#ede9fe; color:#6d28d9; }
.role-user  { background:#dcfce7; color:#166534; }

@media (max-width:900px) {
    .admin-grid { grid-template-columns:1fr; }
    .stat-row   { grid-template-columns:repeat(2,1fr); }
    .zone-grid  { grid-template-columns:1fr; }
}
</style>

{{-- ── HEADER ── --}}
<div class="page-header">
    <div>
        <h1>⚙️ Panel Administrator</h1>
        <p>Kelola kondisi lingkungan per zona status dan akun pengguna sistem SmartFarm</p>
    </div>
    <div class="admin-badge">
        <i data-feather="shield" style="width:13px;height:13px;"></i> Admin
    </div>
</div>

{{-- ── STAT CARDS ── --}}
<div class="stat-row">
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e8ff;">📡</div>
        <div>
            <div class="stat-val" style="color:#7c3aed;">{{ number_format($totalData) }}</div>
            <div class="stat-label">Total Data Sensor</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;">🐛</div>
        <div>
            <div class="stat-val" style="color:#dc2626;">{{ number_format($totalHama) }}</div>
            <div class="stat-label">Data Terdeteksi HAMA</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;">👥</div>
        <div>
            <div class="stat-val" style="color:#16a34a;">{{ $users->count() }}</div>
            <div class="stat-label">Total Pengguna</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;">🛡️</div>
        <div>
            <div class="stat-val" style="color:#d97706;">{{ $users->where('role','admin')->count() }}</div>
            <div class="stat-label">Akun Admin</div>
        </div>
    </div>
</div>

{{-- ── GRID UTAMA ── --}}
<div class="admin-grid">

    {{-- ══ KIRI: KONFIGURASI ZONA ══ --}}
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">🌱 Konfigurasi Kondisi Ideal Tanaman Jagung</div>
        </div>
        <div class="panel-body">

            @php
                // Fallback default jika migration belum dijalankan
                $def = [
                    'suhu_aman'    => ['value'=>22,'min_input'=>5,  'max_input'=>40, 'satuan'=>'°C'],
                    'suhu_waspada' => ['value'=>28,'min_input'=>10, 'max_input'=>42, 'satuan'=>'°C'],
                    'suhu_hama'    => ['value'=>32,'min_input'=>15, 'max_input'=>50, 'satuan'=>'°C'],
                    'udara_aman'   => ['value'=>60,'min_input'=>0,  'max_input'=>100,'satuan'=>'%'],
                    'udara_waspada'=> ['value'=>75,'min_input'=>0,  'max_input'=>100,'satuan'=>'%'],
                    'udara_hama'   => ['value'=>85,'min_input'=>0,  'max_input'=>100,'satuan'=>'%'],
                    'tanah_aman'   => ['value'=>55,'min_input'=>0,  'max_input'=>100,'satuan'=>'%'],
                    'tanah_waspada'=> ['value'=>68,'min_input'=>0,  'max_input'=>100,'satuan'=>'%'],
                    'tanah_hama'   => ['value'=>80,'min_input'=>0,  'max_input'=>100,'satuan'=>'%'],
                ];
                $get = function($key) use ($settings, $def) {
                    $row = $settings->firstWhere('key', $key);
                    return $row ?? (object)array_merge(['key'=>$key], $def[$key] ?? ['value'=>0,'min_input'=>0,'max_input'=>100,'satuan'=>'']);
                };
            @endphp

            @if($settings->firstWhere('key','suhu_aman') === null)
                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:12px 14px; margin-bottom:16px; font-size:12px; color:#92400e; line-height:1.6;">
                    <b>⚠️</b> Jalankan <code style="background:#fef3c7;padding:1px 5px;border-radius:4px;">php artisan migrate</code> agar setting tersimpan permanen. Saat ini menampilkan nilai default.
                </div>
            @endif

            {{-- Info 3 zona --}}
            <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:10px; padding:12px 14px; margin-bottom:20px; font-size:12px; color:#166534; line-height:1.8;">
                <b>ℹ️ Cara pengaturan zona:</b><br>
                <span style="color:#166534;">🟢 <b>AMAN</b></span> → nilai sensor di bawah batas ini = tidak ada hama<br>
                <span style="color:#d97706;">🟡 <b>WASPADA</b></span> → nilai sensor antara AMAN dan WASPADA = potensi hama<br>
                <span style="color:#dc2626;">🔴 <b>HAMA</b></span> → nilai sensor di atas batas ini = ada hama (risiko tinggi)
            </div>

            <form action="{{ route('admin.threshold.update') }}" method="POST" id="formThreshold">
                @csrf

                {{-- ── SUHU UDARA ── --}}
                <div class="param-card">
                    <div class="param-title">
                        <span class="pi" style="background:#fee2e2;">🌡️</span>
                        Suhu Udara (°C)
                    </div>

                    <div class="zone-grid">
                        {{-- AMAN --}}
                        <div class="zone-col">
                            <div class="zone-header zone-aman">🟢 AMAN</div>
                            <label class="zone-label">Batas Maks (°C)</label>
                            <input type="number" step="0.5" id="suhu_aman"
                                name="settings[suhu_aman]"
                                value="{{ old('settings.suhu_aman', $get('suhu_aman')->value) }}"
                                min="{{ $get('suhu_aman')->min_input }}" max="{{ $get('suhu_aman')->max_input }}"
                                class="zone-input inp-aman" oninput="updateBar('suhu')">
                            <div class="zone-hint">Suhu ≤ nilai ini = Tidak ada hama</div>
                        </div>
                        {{-- WASPADA --}}
                        <div class="zone-col">
                            <div class="zone-header zone-waspada">🟡 WASPADA</div>
                            <label class="zone-label">Batas Maks (°C)</label>
                            <input type="number" step="0.5" id="suhu_waspada"
                                name="settings[suhu_waspada]"
                                value="{{ old('settings.suhu_waspada', $get('suhu_waspada')->value) }}"
                                min="{{ $get('suhu_waspada')->min_input }}" max="{{ $get('suhu_waspada')->max_input }}"
                                class="zone-input inp-waspada" oninput="updateBar('suhu')">
                            <div class="zone-hint">Suhu antara AMAN–WASPADA = Potensi hama</div>
                        </div>
                        {{-- HAMA --}}
                        <div class="zone-col">
                            <div class="zone-header zone-hama">🔴 HAMA</div>
                            <label class="zone-label">Batas Min (°C)</label>
                            <input type="number" step="0.5" id="suhu_hama"
                                name="settings[suhu_hama]"
                                value="{{ old('settings.suhu_hama', $get('suhu_hama')->value) }}"
                                min="{{ $get('suhu_hama')->min_input }}" max="{{ $get('suhu_hama')->max_input }}"
                                class="zone-input inp-hama" oninput="updateBar('suhu')">
                            <div class="zone-hint">Suhu ≥ nilai ini = Ada hama!</div>
                        </div>
                    </div>

                    {{-- Bar visual 3 zona --}}
                    <div class="bar-3zona" id="bar-suhu">
                        <div class="bar-aman"    id="baman-suhu"></div>
                        <div class="bar-waspada" id="bwaspada-suhu"></div>
                        <div class="bar-hama"    id="bhama-suhu"></div>
                    </div>
                    <div class="bar-scale">
                        <span>{{ $get('suhu_aman')->min_input }}°C</span>
                        <span id="blabel-suhu" style="font-weight:600; color:#475569;"></span>
                        <span>{{ $get('suhu_hama')->max_input }}°C</span>
                    </div>
                    <div class="bar-legend">
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#22c55e;"></span>AMAN</span>
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#facc15;"></span>WASPADA</span>
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#ef4444;"></span>HAMA</span>
                    </div>
                </div>

                {{-- ── KELEMBAPAN UDARA ── --}}
                <div class="param-card">
                    <div class="param-title">
                        <span class="pi" style="background:#dbeafe;">💧</span>
                        Kelembapan Udara (%)
                    </div>

                    <div class="zone-grid">
                        <div class="zone-col">
                            <div class="zone-header zone-aman">🟢 AMAN</div>
                            <label class="zone-label">Batas Maks (%)</label>
                            <input type="number" step="1" id="udara_aman"
                                name="settings[udara_aman]"
                                value="{{ old('settings.udara_aman', $get('udara_aman')->value) }}"
                                min="0" max="100" class="zone-input inp-aman" oninput="updateBar('udara')">
                            <div class="zone-hint">Udara ≤ nilai ini = Tidak ada hama</div>
                        </div>
                        <div class="zone-col">
                            <div class="zone-header zone-waspada">🟡 WASPADA</div>
                            <label class="zone-label">Batas Maks (%)</label>
                            <input type="number" step="1" id="udara_waspada"
                                name="settings[udara_waspada]"
                                value="{{ old('settings.udara_waspada', $get('udara_waspada')->value) }}"
                                min="0" max="100" class="zone-input inp-waspada" oninput="updateBar('udara')">
                            <div class="zone-hint">Udara antara AMAN–WASPADA = Potensi hama</div>
                        </div>
                        <div class="zone-col">
                            <div class="zone-header zone-hama">🔴 HAMA</div>
                            <label class="zone-label">Batas Min (%)</label>
                            <input type="number" step="1" id="udara_hama"
                                name="settings[udara_hama]"
                                value="{{ old('settings.udara_hama', $get('udara_hama')->value) }}"
                                min="0" max="100" class="zone-input inp-hama" oninput="updateBar('udara')">
                            <div class="zone-hint">Udara ≥ nilai ini = Ada hama!</div>
                        </div>
                    </div>

                    <div class="bar-3zona" id="bar-udara">
                        <div class="bar-aman"    id="baman-udara"></div>
                        <div class="bar-waspada" id="bwaspada-udara"></div>
                        <div class="bar-hama"    id="bhama-udara"></div>
                    </div>
                    <div class="bar-scale">
                        <span>0%</span>
                        <span id="blabel-udara" style="font-weight:600; color:#475569;"></span>
                        <span>100%</span>
                    </div>
                    <div class="bar-legend">
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#22c55e;"></span>AMAN</span>
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#facc15;"></span>WASPADA</span>
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#ef4444;"></span>HAMA</span>
                    </div>
                </div>

                {{-- ── KELEMBAPAN TANAH ── --}}
                <div class="param-card">
                    <div class="param-title">
                        <span class="pi" style="background:#dcfce7;">🌿</span>
                        Kelembapan Tanah (%)
                    </div>

                    <div class="zone-grid">
                        <div class="zone-col">
                            <div class="zone-header zone-aman">🟢 AMAN</div>
                            <label class="zone-label">Batas Maks (%)</label>
                            <input type="number" step="1" id="tanah_aman"
                                name="settings[tanah_aman]"
                                value="{{ old('settings.tanah_aman', $get('tanah_aman')->value) }}"
                                min="0" max="100" class="zone-input inp-aman" oninput="updateBar('tanah')">
                            <div class="zone-hint">Tanah ≤ nilai ini = Tidak ada hama</div>
                        </div>
                        <div class="zone-col">
                            <div class="zone-header zone-waspada">🟡 WASPADA</div>
                            <label class="zone-label">Batas Maks (%)</label>
                            <input type="number" step="1" id="tanah_waspada"
                                name="settings[tanah_waspada]"
                                value="{{ old('settings.tanah_waspada', $get('tanah_waspada')->value) }}"
                                min="0" max="100" class="zone-input inp-waspada" oninput="updateBar('tanah')">
                            <div class="zone-hint">Tanah antara AMAN–WASPADA = Potensi hama</div>
                        </div>
                        <div class="zone-col">
                            <div class="zone-header zone-hama">🔴 HAMA</div>
                            <label class="zone-label">Batas Min (%)</label>
                            <input type="number" step="1" id="tanah_hama"
                                name="settings[tanah_hama]"
                                value="{{ old('settings.tanah_hama', $get('tanah_hama')->value) }}"
                                min="0" max="100" class="zone-input inp-hama" oninput="updateBar('tanah')">
                            <div class="zone-hint">Tanah ≥ nilai ini = Ada hama!</div>
                        </div>
                    </div>

                    <div class="bar-3zona" id="bar-tanah">
                        <div class="bar-aman"    id="baman-tanah"></div>
                        <div class="bar-waspada" id="bwaspada-tanah"></div>
                        <div class="bar-hama"    id="bhama-tanah"></div>
                    </div>
                    <div class="bar-scale">
                        <span>0%</span>
                        <span id="blabel-tanah" style="font-weight:600; color:#475569;"></span>
                        <span>100%</span>
                    </div>
                    <div class="bar-legend">
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#22c55e;"></span>AMAN</span>
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#facc15;"></span>WASPADA</span>
                        <span class="bar-legend-item"><span class="bar-dot" style="background:#ef4444;"></span>HAMA</span>
                    </div>
                </div>

                {{-- Tombol --}}
                <div style="display:flex; gap:12px; margin-top:8px; flex-wrap:wrap; justify-content:flex-end;">
                    <button type="button" class="btn-secondary"
                        onclick="if(confirm('Reset ke nilai default penelitian?')) document.getElementById('formReset').submit()">
                        <i data-feather="rotate-ccw" style="width:14px;height:14px;"></i> Reset Default
                    </button>
                    <button type="submit" class="btn-primary">
                        <i data-feather="save" style="width:14px;height:14px;"></i> Simpan Perubahan
                    </button>
                </div>

            </form>

            <form action="{{ route('admin.threshold.reset') }}" method="POST" id="formReset" style="display:none;">
                @csrf
            </form>

        </div>
    </div>

    {{-- ══ KANAN: MANAJEMEN USER ══ --}}
    <div>
        <div class="panel" style="margin-bottom:24px;">
            <div class="panel-header">
                <div class="panel-title">👥 Manajemen Pengguna</div>
            </div>
            <div class="panel-body">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <div class="form-label"><span>Nama Lengkap</span></div>
                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="Masukkan nama lengkap" class="form-input" autocomplete="off">
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <div class="form-label"><span>Email</span></div>
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="Masukkan email" class="form-input" autocomplete="off">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <div class="form-label"><span>Password</span></div>
                        <div class="password-wrap">
                            <input type="password" name="password" id="pw1"
                                placeholder="Min. 8 karakter" class="form-input">
                            <button type="button" class="toggle-pw" onclick="togglePw('pw1')">
                                <i data-feather="eye" style="width:14px;height:14px;"></i>
                            </button>
                        </div>
                        @error('password')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <div class="form-label"><span>Konfirmasi Password</span></div>
                        <div class="password-wrap">
                            <input type="password" name="password_confirmation" id="pw2"
                                placeholder="Ulangi password" class="form-input">
                            <button type="button" class="toggle-pw" onclick="togglePw('pw2')">
                                <i data-feather="eye" style="width:14px;height:14px;"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-label"><span>Role</span></div>
                        <select name="role" class="form-input" style="cursor:pointer;">
                            <option value="user"  {{ old('role') === 'user'  ? 'selected' : '' }}>👤 User (Petani)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>⚙️ Administrator</option>
                        </select>
                        @error('role')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn-green" style="width:100%; justify-content:center; margin-top:6px;">
                        <i data-feather="user-plus" style="width:14px;height:14px;"></i>
                        Tambah Pengguna
                    </button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Daftar Pengguna ({{ $users->count() }})</div>
            </div>
            <div style="overflow-x:auto;">
                <table class="tabel-data">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                        <tr>
                            <td style="font-weight:600;">
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span style="font-size:10px; background:#dbeafe; color:#1e40af; padding:1px 6px; border-radius:4px; margin-left:4px; font-weight:700;">ANDA</span>
                                @endif
                            </td>
                            <td style="font-size:12px; color:var(--abu); font-family:var(--mono);">{{ $u->email }}</td>
                            <td>
                                <span class="role-badge {{ $u->role === 'admin' ? 'role-admin' : 'role-user' }}">
                                    {{ $u->role === 'admin' ? 'Admin' : 'User' }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                @if($u->id !== auth()->id())
                                    <form action="{{ route('admin.users.delete', $u) }}" method="POST"
                                          onsubmit="return confirm('Hapus pengguna {{ $u->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger">
                                            <i data-feather="trash-2" style="width:12px;height:12px;"></i> Hapus
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size:11px; color:var(--abu);">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center; color:var(--abu); padding:20px;">Belum ada pengguna terdaftar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
function togglePw(id) {
    var el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

// ── Update bar 3 zona ──────────────────────────────────────────
function updateBar(name) {
    var scaleMin = (name === 'suhu') ? parseFloat(document.getElementById(name + '_aman').min) : 0;
    var scaleMax = (name === 'suhu') ? parseFloat(document.getElementById(name + '_hama').max) : 100;
    var range    = scaleMax - scaleMin;

    var vAman    = parseFloat(document.getElementById(name + '_aman').value)    || 0;
    var vWaspada = parseFloat(document.getElementById(name + '_waspada').value) || 0;
    var vHama    = parseFloat(document.getElementById(name + '_hama').value)    || 0;

    var pAman    = Math.max(0, Math.min(100, ((vAman    - scaleMin) / range) * 100));
    var pWaspada = Math.max(0, Math.min(100, ((vWaspada - scaleMin) / range) * 100));
    var pHama    = Math.max(0, Math.min(100, ((vHama    - scaleMin) / range) * 100));

    var bAman    = document.getElementById('baman-'    + name);
    var bWaspada = document.getElementById('bwaspada-' + name);
    var bHama    = document.getElementById('bhama-'    + name);
    var bLabel   = document.getElementById('blabel-'   + name);

    if (bAman)    { bAman.style.width = pAman + '%'; }
    if (bWaspada) { bWaspada.style.left = pAman + '%'; bWaspada.style.width = Math.max(0, pWaspada - pAman) + '%'; }
    if (bHama)    { bHama.style.left = pWaspada + '%'; bHama.style.width = Math.max(0, 100 - pWaspada) + '%'; }

    var unit = (name === 'suhu') ? '°C' : '%';
    if (bLabel) bLabel.innerText = vAman + unit + ' | ' + vWaspada + unit + ' | ' + vHama + unit;
}

// ── Validasi urutan: aman < waspada < hama ──────────────────────
document.getElementById('formThreshold').addEventListener('submit', function(e) {
    var params = ['suhu', 'udara', 'tanah'];
    var labels = {'suhu':'Suhu Udara', 'udara':'Kelembapan Udara', 'tanah':'Kelembapan Tanah'};

    for (var i = 0; i < params.length; i++) {
        var n = params[i];
        var vA = parseFloat(document.getElementById(n + '_aman').value);
        var vW = parseFloat(document.getElementById(n + '_waspada').value);
        var vH = parseFloat(document.getElementById(n + '_hama').value);

        if (vA >= vW) {
            e.preventDefault();
            alert('⚠️ ' + labels[n] + ': Batas AMAN (' + vA + ') harus lebih kecil dari batas WASPADA (' + vW + ').');
            return false;
        }
        if (vW >= vH) {
            e.preventDefault();
            alert('⚠️ ' + labels[n] + ': Batas WASPADA (' + vW + ') harus lebih kecil dari batas HAMA (' + vH + ').');
            return false;
        }
    }
});

// ── Inisialisasi bar saat halaman load ──────────────────────────
window.addEventListener('load', function() {
    ['suhu','udara','tanah'].forEach(updateBar);
});
</script>

@endsection