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
.stat-card { background:white; border:1px solid #e2e8f0; border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.06); display:flex; align-items:center; gap:18px; transition:transform 0.2s, box-shadow 0.2s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(0,0,0,0.1); }
.stat-icon { width:60px; height:60px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:28px; flex-shrink:0; }
.stat-val   { font-size:32px; font-weight:800; color:var(--teks); font-family:var(--mono); line-height:1; }
.stat-label { font-size:12px; color:var(--abu); font-weight:600; margin-top:4px; }

/* Layout dua kolom */
.admin-grid { display:grid; grid-template-columns:1.2fr 1fr; gap:24px; align-items:start; }

/* ── Kartu parameter (Suhu / Udara / Tanah) ── */
.param-card { border:1px solid #e2e8f0; border-radius:14px; padding:24px; margin-bottom:20px; background:white; box-shadow:0 2px 12px rgba(0,0,0,0.04); }
.param-card:last-of-type { margin-bottom:0; }
.param-title { display:flex; align-items:center; gap:12px; font-size:15px; font-weight:700; color:var(--teks); margin-bottom:20px; }
.param-title .pi { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; }

.minmax-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px; }
.minmax-label { font-size:13px; font-weight:600; color:#475569; margin-bottom:8px; display:block; }
.minmax-input {
    width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:10px;
    font-size:14px; font-weight:600; font-family:var(--mono); color:var(--teks);
    background:#fafbfc; transition:border-color 0.15s, box-shadow 0.15s, background 0.15s; outline:none;
}
.minmax-input:focus { border-color:#16a34a; box-shadow:0 0 0 3px rgba(22,163,74,0.1); background:white; }

/* Range bar dua titik */
.range-row { position:relative; height:8px; background:#e2e8f0; border-radius:99px; margin:22px 0 8px; }
.range-fill { position:absolute; top:0; height:100%; background:#16a34a; border-radius:99px; }
.range-dot { position:absolute; top:50%; width:16px; height:16px; background:#16a34a; border:3px solid white; border-radius:50%; transform:translate(-50%,-50%); box-shadow:0 2px 6px rgba(22,163,74,0.3); }
.range-scale { display:flex; justify-content:space-between; font-size:12px; color:#64748b; font-family:var(--mono); font-weight:500; }
.range-optimal { text-align:center; font-size:13px; font-weight:700; color:#16a34a; margin-top:8px; }

/* Tombol */
.btn-primary {
    display:inline-flex; align-items:center; gap:8px;
    background:linear-gradient(135deg,#16a34a,#15803d); color:white;
    border:none; padding:12px 26px; border-radius:10px; cursor:pointer;
    font-size:14px; font-weight:700; font-family:var(--font);
    box-shadow:0 4px 14px rgba(22,163,74,0.3); transition:all 0.2s;
}
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(22,163,74,0.4); }
.btn-primary:active { transform:translateY(0); }

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

/* Form input user */
.form-group { margin-bottom:18px; }
.form-group:last-child { margin-bottom:0; }
.form-label { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.form-label span { font-size:13px; font-weight:600; color:#475569; }
.form-input {
    width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:10px;
    font-size:14px; font-weight:600; font-family:var(--mono); color:var(--teks);
    background:#fafbfc; transition:border-color 0.15s, box-shadow 0.15s, background 0.15s; outline:none;
}
.form-input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,0.1); background:white; }
.form-input::placeholder { color:#cbd5e1; }
.password-wrap { position:relative; }
.password-wrap .form-input { padding-right:42px; }
.toggle-pw { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:0; transition:color 0.2s; }
.toggle-pw:hover { color:#64748b; }
.field-error { font-size:12px; color:#dc2626; margin-top:5px; font-weight:500; }

/* Tabel user */
.role-badge { display:inline-block; padding:4px 12px; border-radius:10px; font-size:12px; font-weight:700; }
.role-admin { background:#ede9fe; color:#6d28d9; }
.role-user  { background:#dcfce7; color:#166534; }

@media (max-width:900px) {
    .admin-grid { grid-template-columns:1fr; }
    .stat-row   { grid-template-columns:repeat(2,1fr); }
    .minmax-grid{ grid-template-columns:1fr; }
}
</style>

{{-- ── HEADER ── --}}
<div class="page-header">
    <div>
        <h1>⚙️ Panel Administrator</h1>
        <p>Kelola kondisi ideal lingkungan tanaman dan akun pengguna sistem SmartFarm</p>
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

    {{-- ══ KIRI: KONFIGURASI KONDISI IDEAL ══ --}}
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">🌱 Konfigurasi Kondisi Ideal Tanaman Jagung</div>
        </div>
        <div class="panel-body">

            @php
                // ── Helper anti-error: jika baris setting belum ada di DB
                // (migration belum jalan / key beda), pakai objek default
                // agar blade TIDAK crash dengan "Attempt to read property
                // value on null". Jalankan migration agar nilai tersimpan
                // permanen ke DB.
                $defaults = [
                    'suhu_min'  => ['value' => 22, 'min_input' => 10, 'max_input' => 35, 'satuan' => '°C'],
                    'suhu_max'  => ['value' => 30, 'min_input' => 15, 'max_input' => 45, 'satuan' => '°C'],
                    'udara_min' => ['value' => 60, 'min_input' => 0,  'max_input' => 100, 'satuan' => '%'],
                    'udara_max' => ['value' => 80, 'min_input' => 0,  'max_input' => 100, 'satuan' => '%'],
                    'tanah_min' => ['value' => 55, 'min_input' => 0,  'max_input' => 100, 'satuan' => '%'],
                    'tanah_max' => ['value' => 75, 'min_input' => 0,  'max_input' => 100, 'satuan' => '%'],
                ];

                $get = function ($key) use ($settings, $defaults) {
                    $row = $settings->firstWhere('key', $key);
                    if ($row) return $row;

                    // bentuk objek sederhana agar bisa diakses dengan -> seperti Eloquent model
                    return (object) array_merge(['key' => $key], $defaults[$key]);
                };

                $suhuMin  = $get('suhu_min');
                $suhuMax  = $get('suhu_max');
                $udaraMin = $get('udara_min');
                $udaraMax = $get('udara_max');
                $tanahMin = $get('tanah_min');
                $tanahMax = $get('tanah_max');
            @endphp

            @if($settings->firstWhere('key','suhu_min') === null)
                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:12px 14px; margin-bottom:16px; font-size:12px; color:#92400e; line-height:1.6;">
                    <b>⚠️ Perhatian:</b> Pengaturan belum tersimpan permanen di database.
                    Jalankan <code>php artisan migrate</code> agar perubahan dapat disimpan.
                    Saat ini menampilkan nilai default sementara.
                </div>
            @endif

            <form action="{{ route('admin.threshold.update') }}" method="POST" id="formThreshold">
                @csrf

                {{-- ── SUHU UDARA ── --}}
                <div class="param-card">
                    <div class="param-title">
                        <span class="pi" style="background:#fee2e2;">🌡️</span>
                        Suhu Udara (°C)
                    </div>
                    <div class="minmax-grid">
                        <div>
                            <label class="minmax-label">Min Ideal (°C)</label>
                            <input type="number" step="0.5"
                                name="settings[suhu_min]"
                                id="suhu_min"
                                value="{{ old('settings.suhu_min', $suhuMin->value) }}"
                                min="{{ $suhuMin->min_input }}" max="{{ $suhuMin->max_input }}"
                                class="minmax-input" oninput="updateRange('suhu')">
                        </div>
                        <div>
                            <label class="minmax-label">Max Ideal (°C)</label>
                            <input type="number" step="0.5"
                                name="settings[suhu_max]"
                                id="suhu_max"
                                value="{{ old('settings.suhu_max', $suhuMax->value) }}"
                                min="{{ $suhuMax->min_input }}" max="{{ $suhuMax->max_input }}"
                                class="minmax-input" oninput="updateRange('suhu')">
                        </div>
                    </div>
                    <div class="range-row" id="bar-suhu">
                        <div class="range-fill" id="fill-suhu"></div>
                        <div class="range-dot" id="dot-min-suhu"></div>
                        <div class="range-dot" id="dot-max-suhu"></div>
                    </div>
                    <div class="range-scale">
                        <span>{{ $suhuMin->min_input }}°C</span>
                        <span>{{ $suhuMax->max_input }}°C</span>
                    </div>
                    <div class="range-optimal" id="label-suhu">
                        Rentang Optimal: {{ $suhuMin->value }}°C - {{ $suhuMax->value }}°C
                    </div>
                </div>

                {{-- ── KELEMBAPAN UDARA ── --}}
                <div class="param-card">
                    <div class="param-title">
                        <span class="pi" style="background:#dbeafe;">💧</span>
                        Kelembapan Udara (%)
                    </div>
                    <div class="minmax-grid">
                        <div>
                            <label class="minmax-label">Min Ideal (%)</label>
                            <input type="number" step="1"
                                name="settings[udara_min]"
                                id="udara_min"
                                value="{{ old('settings.udara_min', $udaraMin->value) }}"
                                min="{{ $udaraMin->min_input }}" max="{{ $udaraMin->max_input }}"
                                class="minmax-input" oninput="updateRange('udara')">
                        </div>
                        <div>
                            <label class="minmax-label">Max Ideal (%)</label>
                            <input type="number" step="1"
                                name="settings[udara_max]"
                                id="udara_max"
                                value="{{ old('settings.udara_max', $udaraMax->value) }}"
                                min="{{ $udaraMax->min_input }}" max="{{ $udaraMax->max_input }}"
                                class="minmax-input" oninput="updateRange('udara')">
                        </div>
                    </div>
                    <div class="range-row" id="bar-udara">
                        <div class="range-fill" id="fill-udara"></div>
                        <div class="range-dot" id="dot-min-udara"></div>
                        <div class="range-dot" id="dot-max-udara"></div>
                    </div>
                    <div class="range-scale">
                        <span>0%</span>
                        <span>100%</span>
                    </div>
                    <div class="range-optimal" id="label-udara">
                        Rentang Optimal: {{ $udaraMin->value }}% - {{ $udaraMax->value }}%
                    </div>
                </div>

                {{-- ── KELEMBAPAN TANAH ── --}}
                <div class="param-card">
                    <div class="param-title">
                        <span class="pi" style="background:#dcfce7;">🌿</span>
                        Kelembapan Tanah (%)
                    </div>
                    <div class="minmax-grid">
                        <div>
                            <label class="minmax-label">Min Ideal (%)</label>
                            <input type="number" step="1"
                                name="settings[tanah_min]"
                                id="tanah_min"
                                value="{{ old('settings.tanah_min', $tanahMin->value) }}"
                                min="{{ $tanahMin->min_input }}" max="{{ $tanahMin->max_input }}"
                                class="minmax-input" oninput="updateRange('tanah')">
                        </div>
                        <div>
                            <label class="minmax-label">Max Ideal (%)</label>
                            <input type="number" step="1"
                                name="settings[tanah_max]"
                                id="tanah_max"
                                value="{{ old('settings.tanah_max', $tanahMax->value) }}"
                                min="{{ $tanahMax->min_input }}" max="{{ $tanahMax->max_input }}"
                                class="minmax-input" oninput="updateRange('tanah')">
                        </div>
                    </div>
                    <div class="range-row" id="bar-tanah">
                        <div class="range-fill" id="fill-tanah"></div>
                        <div class="range-dot" id="dot-min-tanah"></div>
                        <div class="range-dot" id="dot-max-tanah"></div>
                    </div>
                    <div class="range-scale">
                        <span>0%</span>
                        <span>100%</span>
                    </div>
                    <div class="range-optimal" id="label-tanah">
                        Rentang Optimal: {{ $tanahMin->value }}% - {{ $tanahMax->value }}%
                    </div>
                </div>

                {{-- Tombol --}}
                <div style="display:flex; gap:12px; margin-top:8px; flex-wrap:wrap; justify-content:flex-end;">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('formReset').submit()">
                        <i data-feather="rotate-ccw" style="width:14px;height:14px;"></i> Reset Default
                    </button>
                    <button type="submit" class="btn-primary">
                        <i data-feather="save" style="width:14px;height:14px;"></i> Simpan Perubahan
                    </button>
                </div>

            </form>

            {{-- Form reset terpisah (dipicu via tombol di atas) --}}
            <form action="{{ route('admin.threshold.reset') }}" method="POST" id="formReset"
                  onsubmit="return confirm('Reset ke nilai default penelitian?')" style="display:none;">
                @csrf
            </form>

        </div>
    </div>

    {{-- ══ KANAN: MANAJEMEN USER ══ --}}
    <div>

        {{-- Form Tambah User --}}
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

        {{-- Tabel Daftar User --}}
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

// ── Update bar visual untuk satu parameter ──────────────────────
function updateRange(name) {
    var minInput = document.getElementById(name + '_min');
    var maxInput = document.getElementById(name + '_max');
    var fill     = document.getElementById('fill-' + name);
    var dotMin   = document.getElementById('dot-min-' + name);
    var dotMax   = document.getElementById('dot-max-' + name);
    var label    = document.getElementById('label-' + name);

    var scaleMin = parseFloat(minInput.min);
    var scaleMax = parseFloat(maxInput.max);
    var valMin   = parseFloat(minInput.value);
    var valMax   = parseFloat(maxInput.value);

    var pctMin = ((valMin - scaleMin) / (scaleMax - scaleMin)) * 100;
    var pctMax = ((valMax - scaleMin) / (scaleMax - scaleMin)) * 100;
    pctMin = Math.max(0, Math.min(100, pctMin));
    pctMax = Math.max(0, Math.min(100, pctMax));

    if (fill)   { fill.style.left = pctMin + '%'; fill.style.width = Math.max(0, pctMax - pctMin) + '%'; }
    if (dotMin) dotMin.style.left = pctMin + '%';
    if (dotMax) dotMax.style.left = pctMax + '%';

    var unit = (name === 'suhu') ? '°C' : '%';
    if (label) label.innerText = 'Rentang Optimal: ' + valMin + unit + ' - ' + valMax + unit;
}

// ── Validasi: min harus < max ────────────────────────────────────
document.getElementById('formThreshold').addEventListener('submit', function(e) {
    var pairs = [['suhu_min','suhu_max'], ['udara_min','udara_max'], ['tanah_min','tanah_max']];
    for (var i = 0; i < pairs.length; i++) {
        var minVal = parseFloat(document.getElementById(pairs[i][0]).value);
        var maxVal = parseFloat(document.getElementById(pairs[i][1]).value);
        if (minVal >= maxVal) {
            e.preventDefault();
            alert('⚠️ Nilai Min harus lebih kecil dari Max pada parameter ' + pairs[i][0].split('_')[0] + '.');
            return false;
        }
    }
});

// ── Inisialisasi semua bar saat halaman load ─────────────────────
window.addEventListener('load', function () {
    updateRange('suhu');
    updateRange('udara');
    updateRange('tanah');
});
</script>

@endsection