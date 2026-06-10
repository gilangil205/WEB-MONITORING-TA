{{-- ============================================================ --}}
{{-- FILE 10: resources/views/admin/dashboard.blade.php  (FILE BARU) --}}
{{-- ============================================================ --}}
@extends('layouts.app')

@section('title', 'Panel Admin')

@section('content')
<style>
/* ── KHUSUS HALAMAN ADMIN ─────────────────────────────────────── */

.admin-badge {
    display:inline-flex; align-items:center; gap:6px;
    background:linear-gradient(135deg,#7c3aed,#6d28d9);
    color:white; padding:6px 14px; border-radius:99px;
    font-size:12px; font-weight:700;
}

/* Stat cards */
.stat-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
.stat-card { background:white; border:1px solid var(--border); border-radius:var(--radius); padding:16px 18px; box-shadow:var(--shadow); display:flex; align-items:center; gap:12px; }
.stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.stat-val   { font-size:24px; font-weight:700; color:var(--teks); font-family:var(--mono); line-height:1; }
.stat-label { font-size:11px; color:var(--abu); font-weight:500; margin-top:2px; }

/* Layout dua kolom */
.admin-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }

/* Form input */
.form-group { margin-bottom:16px; }
.form-group:last-child { margin-bottom:0; }
.form-label { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
.form-label span { font-size:13px; font-weight:600; color:var(--teks2); }
.form-range-hint { font-size:11px; color:var(--abu); font-family:var(--mono); }
.form-input {
    width:100%; padding:10px 12px; border:1.5px solid #e2e8f0; border-radius:8px;
    font-size:14px; font-weight:600; font-family:var(--mono); color:var(--teks);
    background:#f8fafc; transition:border-color 0.15s, box-shadow 0.15s; outline:none;
}
.form-input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,0.12); background:white; }
.form-input-group { display:flex; align-items:center; gap:8px; }
.form-satuan { font-size:13px; font-weight:600; color:var(--abu); min-width:20px; }
.form-desc   { margin-top:4px; font-size:11px; color:#94a3b8; }

/* Divider dalam panel */
.form-divider { border:none; border-top:1px dashed #e2e8f0; margin:16px 0; }
.form-section-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; color:var(--abu); margin-bottom:12px; }

/* Tombol */
.btn-primary {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg,#7c3aed,#6d28d9); color:white;
    border:none; padding:11px 24px; border-radius:10px; cursor:pointer;
    font-size:14px; font-weight:700; font-family:var(--font);
    box-shadow:0 4px 12px rgba(124,58,237,0.3); transition:all 0.2s;
}
.btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(124,58,237,0.4); }

.btn-secondary {
    display:inline-flex; align-items:center; gap:8px;
    background:white; color:#64748b; border:1.5px solid #e2e8f0;
    padding:10px 20px; border-radius:10px; cursor:pointer;
    font-size:13px; font-weight:600; font-family:var(--font); transition:all 0.15s;
}
.btn-secondary:hover { background:#f8fafc; border-color:#cbd5e1; color:var(--teks); }

.btn-danger {
    display:inline-flex; align-items:center; gap:6px;
    background:#fef2f2; color:#dc2626; border:1px solid #fca5a5;
    padding:6px 12px; border-radius:7px; cursor:pointer;
    font-size:12px; font-weight:600; font-family:var(--font); transition:all 0.15s;
}
.btn-danger:hover { background:#dc2626; color:white; }

.btn-green {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg,#16a34a,#15803d); color:white;
    border:none; padding:11px 24px; border-radius:10px; cursor:pointer;
    font-size:14px; font-weight:700; font-family:var(--font);
    box-shadow:0 4px 12px rgba(22,163,74,0.3); transition:all 0.2s;
}
.btn-green:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(22,163,74,0.4); }

/* Visualisasi skala */
.skala-bar { height:16px; border-radius:99px; overflow:hidden; background:#e2e8f0; margin:8px 0; position:relative; }
.skala-fill-aman    { position:absolute; left:0; top:0; height:100%; background:#22c55e; }
.skala-fill-waspada { position:absolute; top:0; height:100%; background:#facc15; }
.skala-fill-hama    { position:absolute; top:0; height:100%; right:0; background:#ef4444; }
.skala-labels { display:flex; justify-content:space-between; font-size:10px; font-family:var(--mono); color:var(--abu); }

/* Tabel user */
.role-badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:700; }
.role-admin { background:#ede9fe; color:#6d28d9; }
.role-user  { background:#dcfce7; color:#166534; }

/* Password field */
.password-wrap { position:relative; }
.password-wrap .form-input { padding-right:40px; }
.toggle-pw { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--abu); padding:0; }

/* Validation error per field */
.field-error { font-size:12px; color:#dc2626; margin-top:4px; }

@media (max-width:900px) {
    .admin-grid { grid-template-columns:1fr; }
    .stat-row   { grid-template-columns:repeat(2,1fr); }
}
</style>

{{-- ── HEADER ── --}}
<div class="page-header">
    <div>
        <h1>⚙️ Panel Administrator</h1>
        <p>Kelola pengaturan threshold suhu dan akun pengguna sistem SmartFarm</p>
    </div>
    <div class="admin-badge">
        <i data-feather="shield" style="width:13px;height:13px;"></i>
        Admin
    </div>
</div>

{{-- ── STAT CARDS ── --}}
<div class="stat-row">
    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff;">📊</div>
        <div>
            <div class="stat-val">{{ number_format($totalData) }}</div>
            <div class="stat-label">Total Data Sensor (DB)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef2f2;">🚨</div>
        <div>
            <div class="stat-val" style="color:#dc2626;">{{ number_format($totalHama) }}</div>
            <div class="stat-label">Data Terdeteksi HAMA</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;">👥</div>
        <div>
            <div class="stat-val" style="color:#16a34a;">{{ $users->count() }}</div>
            <div class="stat-label">Total Pengguna</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#ede9fe;">🔑</div>
        <div>
            <div class="stat-val" style="color:#7c3aed;">{{ $users->where('role','admin')->count() }}</div>
            <div class="stat-label">Akun Admin</div>
        </div>
    </div>
</div>

{{-- ── GRID UTAMA ── --}}
<div class="admin-grid">

    {{-- ══ PANEL KIRI: THRESHOLD SUHU ══ --}}
    <div>
        <div class="panel" style="margin-bottom:20px;">
            <div class="panel-header">
                <div class="panel-title">🌡️ Pengaturan Threshold Suhu</div>
                <span style="font-size:11px;color:var(--abu);">Mempengaruhi kalkulasi Fuzzy Sugeno</span>
            </div>
            <div class="panel-body">

                {{-- Info singkat --}}
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 14px; margin-bottom:16px; font-size:12px; color:#1e40af; line-height:1.6;">
                    <b>ℹ️</b> Perubahan threshold suhu langsung mempengaruhi hasil deteksi hama
                    di Dashboard, Prediksi, dan Riwayat. Berlaku setelah disimpan.
                </div>

                <form action="{{ route('admin.threshold.update') }}" method="POST" id="formThreshold">
                    @csrf

                    {{-- Batas Suhu Dingin --}}
                    <p class="form-section-title">🥶 Zona Suhu Dingin</p>
                    @foreach($settings->where('key','suhu_dingin_max') as $s)
                    <div class="form-group">
                        <div class="form-label">
                            <span>{{ $s->label }}</span>
                            <span class="form-range-hint">{{ $s->min_input }}–{{ $s->max_input }}{{ $s->satuan }}</span>
                        </div>
                        <div class="form-input-group">
                            <input type="number" name="settings[{{ $s->key }}]"
                                value="{{ old('settings.'.$s->key, $s->value) }}"
                                min="{{ $s->min_input }}" max="{{ $s->max_input }}" step="0.5"
                                class="form-input">
                            <span class="form-satuan">{{ $s->satuan }}</span>
                        </div>
                        @if($s->keterangan)<p class="form-desc">{{ $s->keterangan }}</p>@endif
                    </div>
                    @endforeach

                    <hr class="form-divider">

                    {{-- Batas Suhu Hangat --}}
                    <p class="form-section-title">🌤️ Zona Suhu Hangat</p>
                    @foreach($settings->whereIn('key',['suhu_hangat_min','suhu_hangat_max']) as $s)
                    <div class="form-group">
                        <div class="form-label">
                            <span>{{ $s->label }}</span>
                            <span class="form-range-hint">{{ $s->min_input }}–{{ $s->max_input }}{{ $s->satuan }}</span>
                        </div>
                        <div class="form-input-group">
                            <input type="number" name="settings[{{ $s->key }}]"
                                value="{{ old('settings.'.$s->key, $s->value) }}"
                                min="{{ $s->min_input }}" max="{{ $s->max_input }}" step="0.5"
                                class="form-input">
                            <span class="form-satuan">{{ $s->satuan }}</span>
                        </div>
                        @if($s->keterangan)<p class="form-desc">{{ $s->keterangan }}</p>@endif
                    </div>
                    @endforeach

                    <hr class="form-divider">

                    {{-- Batas Suhu Panas --}}
                    <p class="form-section-title">🔥 Zona Suhu Panas (Berisiko Hama)</p>
                    @foreach($settings->where('key','suhu_panas_min') as $s)
                    <div class="form-group">
                        <div class="form-label">
                            <span>{{ $s->label }}</span>
                            <span class="form-range-hint">{{ $s->min_input }}–{{ $s->max_input }}{{ $s->satuan }}</span>
                        </div>
                        <div class="form-input-group">
                            <input type="number" name="settings[{{ $s->key }}]"
                                value="{{ old('settings.'.$s->key, $s->value) }}"
                                min="{{ $s->min_input }}" max="{{ $s->max_input }}" step="0.5"
                                class="form-input">
                            <span class="form-satuan">{{ $s->satuan }}</span>
                        </div>
                        @if($s->keterangan)<p class="form-desc">{{ $s->keterangan }}</p>@endif
                    </div>
                    @endforeach

                    <hr class="form-divider">

                    {{-- Threshold Fuzzy --}}
                    <p class="form-section-title">⚖️ Batas Status Hama (Nilai Fuzzy 0.0–1.0)</p>

                    @foreach($settings->whereIn('key',['threshold_hama','threshold_waspada'])->sortByDesc('value') as $s)
                    <div class="form-group">
                        <div class="form-label">
                            <span>{{ $s->label }}</span>
                            <span class="form-range-hint">{{ $s->min_input }}–{{ $s->max_input }}</span>
                        </div>
                        <div class="form-input-group">
                            <input type="number" name="settings[{{ $s->key }}]"
                                value="{{ old('settings.'.$s->key, $s->value) }}"
                                min="{{ $s->min_input }}" max="{{ $s->max_input }}" step="0.01"
                                class="form-input"
                                id="input-{{ $s->key }}">
                        </div>
                        @if($s->keterangan)<p class="form-desc">{{ $s->keterangan }}</p>@endif
                    </div>
                    @endforeach

                    {{-- Visualisasi skala threshold --}}
                    @php
                        $tw = $settings->firstWhere('key','threshold_waspada');
                        $th = $settings->firstWhere('key','threshold_hama');
                        $twV = $tw ? $tw->value : 0.45;
                        $thV = $th ? $th->value : 0.70;
                    @endphp
                    <div style="margin-top:4px; padding:12px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                        <div style="font-size:11px; font-weight:600; color:var(--abu); margin-bottom:8px;">📊 Visualisasi Skala Saat Ini</div>
                        <div class="skala-bar">
                            <div class="skala-fill-aman"   style="width:{{ $twV * 100 }}%;"></div>
                            <div class="skala-fill-waspada" style="left:{{ $twV * 100 }}%; width:{{ ($thV - $twV) * 100 }}%;"></div>
                            <div class="skala-fill-hama"   style="left:{{ $thV * 100 }}%;"></div>
                        </div>
                        <div class="skala-labels">
                            <span>0.0 <b style="color:#16a34a;">AMAN</b></span>
                            <span id="viz-tw" style="color:#d97706;font-weight:700;">{{ number_format($twV,2) }} WASPADA</span>
                            <span id="viz-th" style="color:#dc2626;font-weight:700;">{{ number_format($thV,2) }} HAMA</span>
                            <span>1.0</span>
                        </div>
                    </div>

                    {{-- Tombol --}}
                    <div style="display:flex; gap:10px; margin-top:20px; flex-wrap:wrap;">
                        <button type="submit" class="btn-primary">
                            <i data-feather="save" style="width:14px;height:14px;"></i> Simpan
                        </button>
                        <form action="{{ route('admin.threshold.reset') }}" method="POST"
                              onsubmit="return confirm('Reset ke nilai default penelitian?')">
                            @csrf
                            <button type="submit" class="btn-secondary">
                                🔄 Reset Default
                            </button>
                        </form>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- ══ PANEL KANAN: MANAJEMEN USER ══ --}}
    <div>

        {{-- Form Tambah User --}}
        <div class="panel" style="margin-bottom:20px;">
            <div class="panel-header">
                <div class="panel-title">➕ Tambah Pengguna Baru</div>
            </div>
            <div class="panel-body">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <div class="form-label"><span>Nama Lengkap</span></div>
                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="Nama pengguna" class="form-input" autocomplete="off">
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <div class="form-label"><span>Email</span></div>
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="email@contoh.com" class="form-input" autocomplete="off">
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

                    <button type="submit" class="btn-green" style="width:100%; justify-content:center; margin-top:4px;">
                        <i data-feather="user-plus" style="width:14px;height:14px;"></i>
                        Tambah Pengguna
                    </button>
                </form>
            </div>
        </div>

        {{-- Tabel Daftar User --}}
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">👥 Daftar Pengguna ({{ $users->count() }})</div>
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
                                    {{ $u->role === 'admin' ? '⚙️ Admin' : '👤 User' }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                @if($u->id !== auth()->id())
                                    <form action="{{ route('admin.users.delete', $u) }}" method="POST"
                                          onsubmit="return confirm('Hapus pengguna {{ $u->name }}?\nTindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger">
                                            <i data-feather="trash-2" style="width:12px;height:12px;"></i>
                                            Hapus
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size:11px; color:var(--abu);">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center; color:var(--abu); padding:20px;">
                                Belum ada pengguna terdaftar.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- end panel kanan --}}

</div>{{-- end admin-grid --}}

<script>
// ── Toggle password visibility ──────────────────────────────────
function togglePw(id) {
    var el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

// ── Update visualisasi skala threshold secara real-time ─────────
var twInput = document.getElementById('input-threshold_waspada');
var thInput = document.getElementById('input-threshold_hama');

function updateViz() {
    var tw = parseFloat(twInput ? twInput.value : {{ $twV }});
    var th = parseFloat(thInput ? thInput.value : {{ $thV }});
    var vizTw = document.getElementById('viz-tw');
    var vizTh = document.getElementById('viz-th');
    if (vizTw) vizTw.innerText = tw.toFixed(2) + ' WASPADA';
    if (vizTh) vizTh.innerText = th.toFixed(2) + ' HAMA';
}

if (twInput) twInput.addEventListener('input', updateViz);
if (thInput) thInput.addEventListener('input', updateViz);

// ── Validasi client-side sebelum submit threshold ───────────────
document.getElementById('formThreshold').addEventListener('submit', function(e) {
    var tw = parseFloat(twInput ? twInput.value : 0);
    var th = parseFloat(thInput ? thInput.value : 1);

    if (tw >= th) {
        e.preventDefault();
        alert('⚠️ Batas WASPADA (' + tw + ') harus lebih kecil dari batas HAMA (' + th + ').\nSilakan perbaiki sebelum menyimpan.');
        return false;
    }
});
</script>

@endsection