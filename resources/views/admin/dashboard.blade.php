@extends('layouts.app')

@section('title', 'Panel Admin')

@section('content')
<style>
/* ── Layout ── */
.admin-stat-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:1.5rem; }
.admin-stat { background:white; border:0.5px solid #e2e8f0; border-radius:12px; padding:16px; }
.admin-stat-n { font-size:26px; font-weight:500; line-height:1; font-family:var(--mono); }
.admin-stat-l { font-size:12px; color:var(--abu); margin-top:4px; }

.admin-tabs { display:flex; border-bottom:0.5px solid #e2e8f0; margin-bottom:1.5rem; gap:0; }
.admin-tab { padding:9px 18px; font-size:13px; font-weight:600; color:var(--abu); cursor:pointer;
    border-bottom:2px solid transparent; margin-bottom:-1px; transition:color .15s, border-color .15s; }
.admin-tab.active { color:#16a34a; border-bottom-color:#16a34a; }
.admin-tab:hover:not(.active) { color:var(--teks); }

.admin-grid { display:grid; grid-template-columns:1.25fr 1fr; gap:24px; align-items:start; }

/* ── Param cards ── */
.param-card { background:white; border:0.5px solid #e2e8f0; border-radius:12px; padding:18px; margin-bottom:12px; }
.param-card:last-of-type { margin-bottom:0; }
.param-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.param-name { font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px; color:var(--teks); }
.param-unit { font-size:11px; color:var(--abu); font-family:var(--mono); }

/* ── Zone grid ── */
.zone-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:12px; }
.zone-col { display:flex; flex-direction:column; gap:5px; }
.zone-label { font-size:11px; font-weight:600; display:flex; align-items:center; gap:5px; }
.lbl-ok   { color:#3B6D11; }
.lbl-warn { color:#854F0B; }
.lbl-err  { color:#A32D2D; }
.zone-input {
    width:100%; padding:8px 10px; border-radius:8px;
    font-size:13px; font-weight:600; font-family:var(--mono); color:var(--teks);
    background:#fafafa; outline:none; transition:border-color .15s, box-shadow .15s;
}
.inp-ok   { border:1.5px solid #97C459; }
.inp-ok:focus   { border-color:#3B6D11; box-shadow:0 0 0 3px rgba(59,109,17,.1); }
.inp-warn { border:1.5px solid #EF9F27; }
.inp-warn:focus { border-color:#854F0B; box-shadow:0 0 0 3px rgba(133,79,11,.1); }
.inp-err  { border:1.5px solid #F09595; }
.inp-err:focus  { border-color:#A32D2D; box-shadow:0 0 0 3px rgba(163,45,45,.1); }
.zone-hint { font-size:10px; color:var(--abu); }

/* ── Bar ── */
.bar-track { height:8px; border-radius:99px; background:#f1f5f9; position:relative; overflow:hidden; margin:10px 0 3px; }
.bar-ok   { position:absolute; top:0; left:0; height:100%; background:#639922; transition:width .2s; }
.bar-warn { position:absolute; top:0; height:100%; background:#BA7517; transition:left .2s,width .2s; }
.bar-err  { position:absolute; top:0; right:0; height:100%; background:#E24B4A; transition:width .2s; }
.bar-ticks { display:flex; justify-content:space-between; font-size:10px; color:var(--abu); font-family:var(--mono); }
.bar-ticks-mid { font-weight:600; color:#475569; }
.legend-row { display:flex; gap:12px; justify-content:center; margin-top:7px; }
.leg { display:flex; align-items:center; gap:4px; font-size:10px; color:#64748b; font-weight:600; }
.leg-dot { width:8px; height:8px; border-radius:2px; }

/* ── Fuzzy threshold section ── */
.fuzzy-section {
    background:#eff6ff; border:0.5px solid #bfdbfe; border-radius:10px;
    padding:14px 16px; margin-bottom:12px;
}
.fuzzy-head { font-size:12px; font-weight:600; color:#1d4ed8; margin-bottom:4px; display:flex; align-items:center; gap:6px; }
.fuzzy-desc { font-size:11px; color:#3730a3; line-height:1.6; margin-bottom:12px; }
.fuzzy-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.fuzzy-field label { font-size:11px; font-weight:600; color:#1e40af; display:block; margin-bottom:5px; }
.fuzzy-field input {
    width:100%; padding:8px 10px; border:1.5px solid #93c5fd; border-radius:8px;
    font-size:13px; font-weight:600; font-family:var(--mono);
    background:white; color:#1e3a8a; outline:none;
}
.fuzzy-field input:focus { border-color:#1d4ed8; box-shadow:0 0 0 3px rgba(29,78,216,.1); }
.fuzzy-field .zone-hint { color:#3730a3; }
.fuzzy-preview { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center; }
.fuzzy-badge { display:inline-block; padding:2px 9px; border-radius:99px; font-size:10px; font-weight:700; }
.fb-green  { background:#dcfce7; color:#166534; }
.fb-amber  { background:#fef3c7; color:#92400e; }
.fb-red    { background:#fee2e2; color:#991b1b; }

/* ── Buttons ── */
.btn-row { display:flex; gap:8px; justify-content:flex-end; margin-top:16px; }
.btn-save {
    display:inline-flex; align-items:center; gap:6px;
    background:#16a34a; color:white; border:none;
    padding:9px 20px; border-radius:8px; cursor:pointer;
    font-size:13px; font-weight:600; font-family:var(--font); transition:background .15s;
}
.btn-save:hover { background:#15803d; }
.btn-reset {
    display:inline-flex; align-items:center; gap:6px;
    background:white; color:#64748b; border:1px solid #e2e8f0;
    padding:8px 18px; border-radius:8px; cursor:pointer;
    font-size:13px; font-weight:600; font-family:var(--font); transition:all .15s;
}
.btn-reset:hover { background:#f8fafc; border-color:#cbd5e1; }

/* ── Validation error ── */
.val-error { background:#fef2f2; border:0.5px solid #fca5a5; border-radius:8px;
    padding:10px 14px; font-size:12px; color:#991b1b; margin-bottom:12px; line-height:1.6; }

/* ── User form (kolom kanan) ── */
.form-group { margin-bottom:14px; }
.form-group:last-child { margin-bottom:0; }
.form-label-text { font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:5px; }
.form-input {
    width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:8px;
    font-size:13px; font-weight:600; font-family:var(--mono); color:var(--teks);
    background:#fafafa; outline:none; transition:border-color .15s;
}
.form-input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1); background:white; }
.form-input::placeholder { color:#cbd5e1; font-weight:400; }
.password-wrap { position:relative; }
.password-wrap .form-input { padding-right:40px; }
.toggle-pw { position:absolute; right:10px; top:50%; transform:translateY(-50%);
    background:none; border:none; cursor:pointer; color:#94a3b8; padding:0; }
.toggle-pw:hover { color:#64748b; }
.field-error { font-size:11px; color:#dc2626; margin-top:4px; }
.role-badge { display:inline-block; padding:3px 10px; border-radius:8px; font-size:11px; font-weight:700; }
.role-admin { background:#ede9fe; color:#6d28d9; }
.role-user  { background:#dcfce7; color:#166534; }
.btn-add {
    width:100%; display:flex; align-items:center; justify-content:center; gap:6px;
    background:#16a34a; color:white; border:none; padding:10px;
    border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; font-family:var(--font);
    margin-top:4px; transition:background .15s;
}
.btn-add:hover { background:#15803d; }
.btn-del {
    display:inline-flex; align-items:center; gap:5px;
    background:#fef2f2; color:#dc2626; border:1px solid #fca5a5;
    padding:5px 12px; border-radius:6px; cursor:pointer; font-size:11px; font-weight:600;
    font-family:var(--font); transition:all .15s;
}
.btn-del:hover { background:#dc2626; color:white; border-color:#dc2626; }

@media (max-width:900px) {
    .admin-grid       { grid-template-columns:1fr; }
    .admin-stat-row   { grid-template-columns:repeat(2,1fr); }
    .zone-grid        { grid-template-columns:1fr; }
    .fuzzy-grid       { grid-template-columns:1fr; }
}
</style>

{{-- HEADER --}}
<div class="page-header">
    <div>
        <h1>⚙️ Panel Administrator</h1>
        <p>Kelola threshold sensor dan pengguna sistem SmartFarm</p>
    </div>
    <span style="display:inline-flex;align-items:center;gap:6px;background:#7c3aed;color:white;
        padding:6px 14px;border-radius:99px;font-size:12px;font-weight:700;">
        <i data-feather="shield" style="width:12px;height:12px;"></i> Admin
    </span>
</div>

{{-- STAT CARDS --}}
<div class="admin-stat-row">
    <div class="admin-stat">
        <div class="admin-stat-n" style="color:#7c3aed;">{{ number_format($totalData) }}</div>
        <div class="admin-stat-l">📡 Total data sensor</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-n" style="color:#dc2626;">{{ number_format($totalHama) }}</div>
        <div class="admin-stat-l">🐛 Terdeteksi hama</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-n" style="color:#16a34a;">{{ $users->count() }}</div>
        <div class="admin-stat-l">👥 Total pengguna</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-n" style="color:#d97706;">{{ $users->where('role','admin')->count() }}</div>
        <div class="admin-stat-l">🛡️ Akun admin</div>
    </div>
</div>

{{-- TABS --}}
<div class="admin-tabs">
    <div class="admin-tab active" onclick="switchTab('threshold', this)">Konfigurasi threshold</div>
    <div class="admin-tab"        onclick="switchTab('users',     this)">Pengguna</div>
    {{-- 🆕 TAB RIWAYAT — redirect ke halaman riwayat admin --}}
    <div class="admin-tab"        onclick="location.href='{{ route('admin.riwayat') }}'">📋 Riwayat</div>
</div>

{{-- ============================================================ --}}
{{-- TAB: THRESHOLD                                               --}}
{{-- ============================================================ --}}
<div id="tab-threshold">
<div class="admin-grid">

    {{-- KIRI: Form threshold --}}
    <div>
        @if(session('success'))
            <div style="background:#f0fdf4;border:0.5px solid #86efac;border-radius:8px;
                padding:10px 14px;font-size:12px;color:#166534;margin-bottom:16px;font-weight:600;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="val-error">{{ session('error') }}</div>
        @endif

        @php
            $def = [
                'suhu_aman'         => ['value'=>22,   'min_input'=>5,  'max_input'=>40],
                'suhu_waspada'      => ['value'=>28,   'min_input'=>10, 'max_input'=>42],
                'suhu_hama'         => ['value'=>32,   'min_input'=>15, 'max_input'=>50],
                'udara_aman'        => ['value'=>60,   'min_input'=>0,  'max_input'=>100],
                'udara_waspada'     => ['value'=>75,   'min_input'=>0,  'max_input'=>100],
                'udara_hama'        => ['value'=>85,   'min_input'=>0,  'max_input'=>100],
                'tanah_aman'        => ['value'=>55,   'min_input'=>0,  'max_input'=>100],
                'tanah_waspada'     => ['value'=>68,   'min_input'=>0,  'max_input'=>100],
                'tanah_hama'        => ['value'=>80,   'min_input'=>0,  'max_input'=>100],
                'threshold_hama'    => ['value'=>0.70, 'min_input'=>0.51,'max_input'=>0.99],
                'threshold_waspada' => ['value'=>0.45, 'min_input'=>0.01,'max_input'=>0.69],
            ];
            $get = function($key) use ($settings, $def) {
                $row = $settings->firstWhere('key', $key);
                return $row ?? (object)array_merge(['key'=>$key], $def[$key] ?? ['value'=>0,'min_input'=>0,'max_input'=>100]);
            };
        @endphp

        <form action="{{ route('admin.threshold.update') }}" method="POST" id="formThreshold">
            @csrf

            {{-- SUHU UDARA --}}
            <div class="param-card">
                <div class="param-head">
                    <div class="param-name">🌡️ Suhu udara</div>
                    <span class="param-unit">satuan: °C</span>
                </div>
                <div class="zone-grid">
                    <div class="zone-col">
                        <label class="zone-label lbl-ok">✅ Zona aman — batas maks</label>
                        <input type="number" step="0.5" id="suhu_aman"
                            name="settings[suhu_aman]"
                            value="{{ old('settings.suhu_aman', $get('suhu_aman')->value) }}"
                            min="{{ $get('suhu_aman')->min_input }}"
                            max="{{ $get('suhu_aman')->max_input }}"
                            class="zone-input inp-ok" oninput="updateBar('suhu')">
                        <div class="zone-hint">Suhu ≤ nilai ini → tidak ada hama</div>
                    </div>
                    <div class="zone-col">
                        <label class="zone-label lbl-warn">⚠️ Zona waspada — batas maks</label>
                        <input type="number" step="0.5" id="suhu_waspada"
                            name="settings[suhu_waspada]"
                            value="{{ old('settings.suhu_waspada', $get('suhu_waspada')->value) }}"
                            min="{{ $get('suhu_waspada')->min_input }}"
                            max="{{ $get('suhu_waspada')->max_input }}"
                            class="zone-input inp-warn" oninput="updateBar('suhu')">
                        <div class="zone-hint">Suhu antara aman–waspada → potensi hama</div>
                    </div>
                    <div class="zone-col">
                        <label class="zone-label lbl-err">🔴 Zona hama — batas min</label>
                        <input type="number" step="0.5" id="suhu_hama"
                            name="settings[suhu_hama]"
                            value="{{ old('settings.suhu_hama', $get('suhu_hama')->value) }}"
                            min="{{ $get('suhu_hama')->min_input }}"
                            max="{{ $get('suhu_hama')->max_input }}"
                            class="zone-input inp-err" oninput="updateBar('suhu')">
                        <div class="zone-hint">Suhu ≥ nilai ini → ada hama</div>
                    </div>
                </div>
                <div class="bar-track">
                    <div class="bar-ok"   id="bok-suhu"></div>
                    <div class="bar-warn" id="bwarn-suhu"></div>
                    <div class="bar-err"  id="berr-suhu"></div>
                </div>
                <div class="bar-ticks">
                    <span>{{ $get('suhu_aman')->min_input }}°C</span>
                    <span class="bar-ticks-mid" id="blabel-suhu"></span>
                    <span>{{ $get('suhu_hama')->max_input }}°C</span>
                </div>
                <div class="legend-row">
                    <span class="leg"><span class="leg-dot" style="background:#639922"></span>Aman</span>
                    <span class="leg"><span class="leg-dot" style="background:#BA7517"></span>Waspada</span>
                    <span class="leg"><span class="leg-dot" style="background:#E24B4A"></span>Hama</span>
                </div>
            </div>

            {{-- KELEMBAPAN UDARA --}}
            <div class="param-card">
                <div class="param-head">
                    <div class="param-name">💧 Kelembapan udara</div>
                    <span class="param-unit">satuan: %</span>
                </div>
                <div class="zone-grid">
                    <div class="zone-col">
                        <label class="zone-label lbl-ok">✅ Zona aman — batas maks</label>
                        <input type="number" step="1" id="udara_aman"
                            name="settings[udara_aman]"
                            value="{{ old('settings.udara_aman', $get('udara_aman')->value) }}"
                            min="0" max="100" class="zone-input inp-ok" oninput="updateBar('udara')">
                        <div class="zone-hint">Udara ≤ nilai ini → tidak ada hama</div>
                    </div>
                    <div class="zone-col">
                        <label class="zone-label lbl-warn">⚠️ Zona waspada — batas maks</label>
                        <input type="number" step="1" id="udara_waspada"
                            name="settings[udara_waspada]"
                            value="{{ old('settings.udara_waspada', $get('udara_waspada')->value) }}"
                            min="0" max="100" class="zone-input inp-warn" oninput="updateBar('udara')">
                        <div class="zone-hint">Udara antara aman–waspada → potensi hama</div>
                    </div>
                    <div class="zone-col">
                        <label class="zone-label lbl-err">🔴 Zona hama — batas min</label>
                        <input type="number" step="1" id="udara_hama"
                            name="settings[udara_hama]"
                            value="{{ old('settings.udara_hama', $get('udara_hama')->value) }}"
                            min="0" max="100" class="zone-input inp-err" oninput="updateBar('udara')">
                        <div class="zone-hint">Udara ≥ nilai ini → ada hama</div>
                    </div>
                </div>
                <div class="bar-track">
                    <div class="bar-ok"   id="bok-udara"></div>
                    <div class="bar-warn" id="bwarn-udara"></div>
                    <div class="bar-err"  id="berr-udara"></div>
                </div>
                <div class="bar-ticks">
                    <span>0%</span>
                    <span class="bar-ticks-mid" id="blabel-udara"></span>
                    <span>100%</span>
                </div>
                <div class="legend-row">
                    <span class="leg"><span class="leg-dot" style="background:#639922"></span>Aman</span>
                    <span class="leg"><span class="leg-dot" style="background:#BA7517"></span>Waspada</span>
                    <span class="leg"><span class="leg-dot" style="background:#E24B4A"></span>Hama</span>
                </div>
            </div>

            {{-- KELEMBAPAN TANAH --}}
            <div class="param-card">
                <div class="param-head">
                    <div class="param-name">🌱 Kelembapan tanah</div>
                    <span class="param-unit">satuan: %</span>
                </div>
                <div class="zone-grid">
                    <div class="zone-col">
                        <label class="zone-label lbl-ok">✅ Zona aman — batas maks</label>
                        <input type="number" step="1" id="tanah_aman"
                            name="settings[tanah_aman]"
                            value="{{ old('settings.tanah_aman', $get('tanah_aman')->value) }}"
                            min="0" max="100" class="zone-input inp-ok" oninput="updateBar('tanah')">
                        <div class="zone-hint">Tanah ≤ nilai ini → tidak ada hama</div>
                    </div>
                    <div class="zone-col">
                        <label class="zone-label lbl-warn">⚠️ Zona waspada — batas maks</label>
                        <input type="number" step="1" id="tanah_waspada"
                            name="settings[tanah_waspada]"
                            value="{{ old('settings.tanah_waspada', $get('tanah_waspada')->value) }}"
                            min="0" max="100" class="zone-input inp-warn" oninput="updateBar('tanah')">
                        <div class="zone-hint">Tanah antara aman–waspada → potensi hama</div>
                    </div>
                    <div class="zone-col">
                        <label class="zone-label lbl-err">🔴 Zona hama — batas min</label>
                        <input type="number" step="1" id="tanah_hama"
                            name="settings[tanah_hama]"
                            value="{{ old('settings.tanah_hama', $get('tanah_hama')->value) }}"
                            min="0" max="100" class="zone-input inp-err" oninput="updateBar('tanah')">
                        <div class="zone-hint">Tanah ≥ nilai ini → ada hama</div>
                    </div>
                </div>
                <div class="bar-track">
                    <div class="bar-ok"   id="bok-tanah"></div>
                    <div class="bar-warn" id="bwarn-tanah"></div>
                    <div class="bar-err"  id="berr-tanah"></div>
                </div>
                <div class="bar-ticks">
                    <span>0%</span>
                    <span class="bar-ticks-mid" id="blabel-tanah"></span>
                    <span>100%</span>
                </div>
                <div class="legend-row">
                    <span class="leg"><span class="leg-dot" style="background:#639922"></span>Aman</span>
                    <span class="leg"><span class="leg-dot" style="background:#BA7517"></span>Waspada</span>
                    <span class="leg"><span class="leg-dot" style="background:#E24B4A"></span>Hama</span>
                </div>
            </div>

            {{-- THRESHOLD KEPUTUSAN FUZZY --}}
            <div class="fuzzy-section">
                <div class="fuzzy-head">
                    🔢 Threshold keputusan Fuzzy Sugeno
                </div>
                <div class="fuzzy-desc">
                    Output fuzzy (0–1) dibandingkan dengan dua nilai ini untuk menentukan status akhir.
                    Perubahan di sini langsung memengaruhi klasifikasi HAMA / WASPADA / AMAN tanpa mengubah kode.
                </div>
                <div class="fuzzy-grid">
                    <div class="fuzzy-field">
                        <label>Batas minimum HAMA (nilai fuzzy ≥ ini → HAMA)</label>
                        <input type="number" step="0.01"
                            id="threshold_hama"
                            name="settings[threshold_hama]"
                            value="{{ old('settings.threshold_hama', $get('threshold_hama')->value) }}"
                            min="{{ $get('threshold_hama')->min_input ?? 0.51 }}"
                            max="{{ $get('threshold_hama')->max_input ?? 0.99 }}"
                            oninput="updateFuzzyPreview()">
                        <div class="zone-hint" style="color:#3730a3;">Default: 0.70</div>
                    </div>
                    <div class="fuzzy-field">
                        <label>Batas minimum WASPADA (nilai fuzzy ≥ ini → WASPADA)</label>
                        <input type="number" step="0.01"
                            id="threshold_waspada"
                            name="settings[threshold_waspada]"
                            value="{{ old('settings.threshold_waspada', $get('threshold_waspada')->value) }}"
                            min="{{ $get('threshold_waspada')->min_input ?? 0.01 }}"
                            max="{{ $get('threshold_waspada')->max_input ?? 0.69 }}"
                            oninput="updateFuzzyPreview()">
                        <div class="zone-hint" style="color:#3730a3;">Default: 0.45</div>
                    </div>
                </div>
                <div id="fuzzy-preview" class="fuzzy-preview"></div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn-reset"
                    onclick="if(confirm('Reset semua ke nilai default penelitian?')) document.getElementById('formReset').submit()">
                    <i data-feather="rotate-ccw" style="width:13px;height:13px;"></i> Reset default
                </button>
                <button type="submit" class="btn-save">
                    <i data-feather="save" style="width:13px;height:13px;"></i> Simpan perubahan
                </button>
            </div>
        </form>

        <form action="{{ route('admin.threshold.reset') }}" method="POST" id="formReset" style="display:none;">
            @csrf
        </form>
    </div>

    {{-- KANAN: Pengguna (tetap di sisi kanan pada tab threshold) --}}
    <div>
        <div class="panel" style="margin-bottom:16px;">
            <div class="panel-header">
                <div class="panel-title">👥 Tambah pengguna</div>
            </div>
            <div class="panel-body">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <span class="form-label-text">Nama lengkap</span>
                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="cth. Budi Santoso" class="form-input" autocomplete="off">
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <span class="form-label-text">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="cth. budi@kebun.id" class="form-input" autocomplete="off">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <span class="form-label-text">Password</span>
                        <div class="password-wrap">
                            <input type="password" name="password" id="pw1"
                                placeholder="Min. 8 karakter" class="form-input">
                            <button type="button" class="toggle-pw" onclick="togglePw('pw1')">
                                <i data-feather="eye" style="width:13px;height:13px;"></i>
                            </button>
                        </div>
                        @error('password')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <span class="form-label-text">Konfirmasi password</span>
                        <div class="password-wrap">
                            <input type="password" name="password_confirmation" id="pw2"
                                placeholder="Ulangi password" class="form-input">
                            <button type="button" class="toggle-pw" onclick="togglePw('pw2')">
                                <i data-feather="eye" style="width:13px;height:13px;"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <span class="form-label-text">Role</span>
                        <select name="role" class="form-input" style="cursor:pointer;">
                            <option value="user"  {{ old('role') === 'user'  ? 'selected' : '' }}>👤 User (petani)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>⚙️ Administrator</option>
                        </select>
                        @error('role')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-add">
                        <i data-feather="user-plus" style="width:13px;height:13px;"></i> Tambah pengguna
                    </button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Daftar pengguna ({{ $users->count() }})</div>
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
                            <td style="font-weight:600;font-size:13px;">
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span style="font-size:10px;background:#dbeafe;color:#1e40af;
                                        padding:1px 6px;border-radius:4px;margin-left:4px;font-weight:700;">ANDA</span>
                                @endif
                            </td>
                            <td style="font-size:11px;color:var(--abu);font-family:var(--mono);">{{ $u->email }}</td>
                            <td>
                                <span class="role-badge {{ $u->role === 'admin' ? 'role-admin' : 'role-user' }}">
                                    {{ $u->role === 'admin' ? 'Admin' : 'User' }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                @if($u->id !== auth()->id())
                                    <form action="{{ route('admin.users.delete', $u) }}" method="POST"
                                          onsubmit="return confirm('Hapus pengguna {{ $u->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-del">
                                            <i data-feather="trash-2" style="width:11px;height:11px;"></i> Hapus
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size:11px;color:var(--abu);">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center;color:var(--abu);padding:20px;">
                            Belum ada pengguna terdaftar.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

{{-- ============================================================ --}}
{{-- TAB: USERS (standalone, untuk mobile)                        --}}
{{-- ============================================================ --}}
<div id="tab-users" style="display:none;">
    <p style="font-size:13px;color:var(--abu);text-align:center;padding:24px;">
        Gunakan kolom kanan pada tab "Konfigurasi threshold" untuk manajemen pengguna.
    </p>
</div>

<script>
function switchTab(name, el) {
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('tab-threshold').style.display = name === 'threshold' ? '' : 'none';
    document.getElementById('tab-users').style.display     = name === 'users'     ? '' : 'none';
}

function togglePw(id) {
    var el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

function updateBar(name) {
    var isS = name === 'suhu';
    var scaleMin = isS ? parseFloat(document.getElementById(name + '_aman').min) : 0;
    var scaleMax = isS ? parseFloat(document.getElementById(name + '_hama').max) : 100;
    var range    = scaleMax - scaleMin;
    var a = parseFloat(document.getElementById(name + '_aman').value)    || 0;
    var w = parseFloat(document.getElementById(name + '_waspada').value) || 0;
    var h = parseFloat(document.getElementById(name + '_hama').value)    || 0;
    var pa = Math.max(0, Math.min(100, ((a - scaleMin) / range) * 100));
    var pw = Math.max(0, Math.min(100, ((w - scaleMin) / range) * 100));
    document.getElementById('bok-'   + name).style.width = pa + '%';
    document.getElementById('bwarn-' + name).style.left  = pa + '%';
    document.getElementById('bwarn-' + name).style.width = Math.max(0, pw - pa) + '%';
    document.getElementById('berr-'  + name).style.width = Math.max(0, 100 - pw) + '%';
    var unit = isS ? '°C' : '%';
    var lbl = document.getElementById('blabel-' + name);
    if (lbl) lbl.innerText = a + unit + ' | ' + w + unit + ' | ' + h + unit;
}

function updateFuzzyPreview() {
    var h = parseFloat(document.getElementById('threshold_hama').value)    || 0.70;
    var w = parseFloat(document.getElementById('threshold_waspada').value) || 0.45;
    var box = document.getElementById('fuzzy-preview');
    if (!box) return;
    box.innerHTML =
        '<span style="font-size:10px;color:#3730a3;font-weight:600;">Zona output saat ini:</span>' +
        '<span class="fuzzy-badge fb-green">0.00 – ' + (w - 0.01).toFixed(2) + ' → AMAN</span>' +
        '<span class="fuzzy-badge fb-amber">' + w.toFixed(2) + ' – ' + (h - 0.01).toFixed(2) + ' → WASPADA</span>' +
        '<span class="fuzzy-badge fb-red">' + h.toFixed(2) + ' – 1.00 → HAMA</span>';
}

// Validasi form sebelum submit
document.getElementById('formThreshold').addEventListener('submit', function(e) {
    var params  = ['suhu', 'udara', 'tanah'];
    var labels  = { suhu: 'Suhu udara', udara: 'Kelembapan udara', tanah: 'Kelembapan tanah' };
    for (var i = 0; i < params.length; i++) {
        var n  = params[i];
        var vA = parseFloat(document.getElementById(n + '_aman').value);
        var vW = parseFloat(document.getElementById(n + '_waspada').value);
        var vH = parseFloat(document.getElementById(n + '_hama').value);
        if (vA >= vW) {
            e.preventDefault();
            alert('⚠️ ' + labels[n] + ': batas aman (' + vA + ') harus lebih kecil dari batas waspada (' + vW + ').');
            return false;
        }
        if (vW >= vH) {
            e.preventDefault();
            alert('⚠️ ' + labels[n] + ': batas waspada (' + vW + ') harus lebih kecil dari batas hama (' + vH + ').');
            return false;
        }
    }
    var th = parseFloat(document.getElementById('threshold_hama').value);
    var tw = parseFloat(document.getElementById('threshold_waspada').value);
    if (tw >= th) {
        e.preventDefault();
        alert('⚠️ Threshold WASPADA (' + tw + ') harus lebih kecil dari threshold HAMA (' + th + ').');
        return false;
    }
});

window.addEventListener('load', function () {
    ['suhu', 'udara', 'tanah'].forEach(updateBar);
    updateFuzzyPreview();
});
</script>

@endsection