@extends('layouts.app')

@section('title', 'Admin - Riwayat Data Sensor')

@section('content')

<style>
.btn-delete {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 18px;
    padding: 4px 8px;
    transition: transform 0.1s;
}
.btn-delete:hover {
    transform: scale(1.2);
}
.btn-delete-all {
    display:inline-block;
    padding:8px 14px;
    border-radius:8px;
    background:#fee2e2;
    color:#dc2626;
    border:1px solid #fca5a5;
    cursor:pointer;
    font-size:13px;
    font-weight:600;
}
.btn-delete-all:hover {
    background:#fecaca;
}
</style>

<div class="page-header">
    <div>
        <h1>📋 Riwayat Data Sensor</h1>
        <p>Kelola seluruh data sensor yang tersimpan di database</p>
    </div>
    <span style="display:inline-flex;align-items:center;gap:6px;background:#7c3aed;color:white;
        padding:6px 14px;border-radius:99px;font-size:12px;font-weight:700;">
        <i data-feather="shield" style="width:12px;height:12px;"></i> Admin
    </span>
</div>

{{-- BACK TO DASHBOARD --}}
<div style="margin-bottom:16px;">
    <a href="{{ route('admin.dashboard') }}"
       style="color:#7c3aed; text-decoration:none; font-size:13px; font-weight:600;">
        ← Kembali ke Dashboard Admin
    </a>
</div>

{{-- NOTIFIKASI --}}
@if(session('success'))
<div style="background:#f0fdf4;border:0.5px solid #86efac;border-radius:8px;
    padding:10px 14px;font-size:13px;color:#166534;margin-bottom:16px;font-weight:600;">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fef2f2;border:0.5px solid #fca5a5;border-radius:8px;
    padding:10px 14px;font-size:13px;color:#991b1b;margin-bottom:16px;font-weight:600;">
    {{ session('error') }}
</div>
@endif

{{-- FORM FILTER --}}
<form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; align-items:center;">

    <div>
        <label style="font-size:13px; color:#64748b; display:block; margin-bottom:4px;">Periode Waktu</label>
        <select name="filter" onchange="this.form.submit()"
            style="padding:8px 12px; border-radius:8px; border:1px solid #ddd; font-size:13px; cursor:pointer;">
            <option value="">Semua Data</option>
            <option value="7hari"  {{ request('filter')=='7hari'  ? 'selected':'' }}>7 Hari Terakhir</option>
            <option value="1bulan" {{ request('filter')=='1bulan' ? 'selected':'' }}>1 Bulan Terakhir</option>
            <option value="3bulan" {{ request('filter')=='3bulan' ? 'selected':'' }}>3 Bulan Terakhir</option>
        </select>
    </div>

    <div>
        <label style="font-size:13px; color:#64748b; display:block; margin-bottom:4px;">Status Deteksi</label>
        <select name="deteksi" onchange="this.form.submit()"
            style="padding:8px 12px; border-radius:8px; border:1px solid #ddd; font-size:13px; cursor:pointer;">
            <option value="">Semua Status</option>
            <option value="HAMA"    {{ request('deteksi')=='HAMA'    ? 'selected':'' }}>🔴 HAMA</option>
            <option value="WASPADA" {{ request('deteksi')=='WASPADA' ? 'selected':'' }}>🟡 WASPADA</option>
            <option value="AMAN"    {{ request('deteksi')=='AMAN'    ? 'selected':'' }}>🟢 AMAN</option>
        </select>
    </div>

    <div style="align-self:flex-end; display:flex; gap:8px;">
        <a href="{{ route('admin.riwayat') }}"
           style="display:inline-block; padding:8px 14px; border-radius:8px; background:#f1f5f9; color:#475569; text-decoration:none; font-size:13px; border:1px solid #ddd;">
            🔄 Reset
        </a>

        <button type="button" id="btn-delete-all" class="btn-delete-all">
            🗑️ Hapus Semua
        </button>
    </div>

</form>

{{-- RINGKASAN --}}
@if($data->total() > 0)
<div style="background:#f8fafc; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; gap:24px; flex-wrap:wrap; font-size:13px; color:#475569;">
    <span>📊 Total data: <b>{{ $data->total() }}</b></span>
    <span>🔴 HAMA: <b>{{ $data->getCollection()->where('status','HAMA')->count() }}</b></span>
    <span>🟡 WASPADA: <b>{{ $data->getCollection()->where('status','WASPADA')->count() }}</b></span>
    <span>🟢 AMAN: <b>{{ $data->getCollection()->where('status','AMAN')->count() }}</b></span>
</div>
@endif

{{-- TABEL --}}
<div style="overflow-x:auto;">

    <table class="smart-table">

        <thead>
            <tr>
                <th>No</th>
                <th>Waktu</th>
                <th>Suhu</th>
                <th>Udara</th>
                <th>Tanah</th>
                <th>Nilai Fuzzy</th>
                <th>Status</th>
                <th>Hasil YOLO</th>
                <th>Confidence</th>
                <th>Gambar</th>
                <th style="text-align:center;">Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($data as $i => $item)
            <tr id="row-{{ $item->id }}">

                <td style="color:#94a3b8; font-size:13px;">
                    {{ $data->firstItem() + $i }}
                </td>

                <td style="white-space:nowrap;">
                    {{ $item->created_at->format('d M Y') }}<br>
                    <small style="color:#94a3b8;">{{ $item->created_at->format('H:i') }}</small>
                </td>

                <td><span class="cell suhu">{{ $item->suhu }}°C</span></td>
                <td><span class="cell udara">{{ $item->kelembapan_udara }}%</span></td>
                <td><span class="cell tanah">{{ $item->kelembapan_tanah }}%</span></td>

                <td style="text-align:center; font-weight:600; color:#475569;">
                    {{ number_format($item->nilai, 3) }}
                </td>

                <td>
                    <span class="{{ $item->status == 'HAMA' ? 'status-high' : ($item->status == 'WASPADA' ? 'status-medium' : 'status-low') }}">
                        {{ $item->status }}
                    </span>
                </td>

                <td>
                    @if($item->deteksi_yolo)
                        <span class="{{ $item->deteksi_yolo == 'Tikus Terdeteksi' ? 'status-high' : 'status-low' }}">
                            {{ $item->deteksi_yolo }}
                        </span>
                    @else
                        <span style="color:#cbd5e1; font-size:12px;">Tidak ada</span>
                    @endif
                </td>

                <td>
                    @if($item->confidence_yolo)
                        {{ number_format($item->confidence_yolo * 100, 2) }}%
                    @else
                        <span style="color:#cbd5e1; font-size:12px;">-</span>
                    @endif
                </td>

                <td>
                    @if($item->image)
                        <a href="{{ asset('storage/'.$item->image) }}" target="_blank">
                            <img src="{{ asset('storage/'.$item->image) }}"
                                 width="60"
                                 style="border-radius:6px; cursor:pointer; border:2px solid #e2e8f0;"
                                 title="Klik untuk perbesar">
                        </a>
                    @else
                        <span style="color:#cbd5e1; font-size:12px;">Tidak ada</span>
                    @endif
                </td>

                <td style="text-align:center;">
                    <form action="{{ route('admin.riwayat.delete', $item->id) }}" method="POST"
                          onsubmit="return confirm('Yakin ingin menghapus data ini?');"
                          style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-delete" title="Hapus data">
                            🗑️
                        </button>
                    </form>
                </td>

            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align:center; padding:30px; color:#94a3b8;">
                    📭 Belum ada data yang ditemukan
                </td>
            </tr>
            @endforelse
        </tbody>

    </table>

</div>

{{-- PAGINATION --}}
@if($data->total() > 0)
<div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; flex-wrap:wrap; gap:10px;">
    <div style="font-size:13px; color:#64748b;">
        Menampilkan {{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }}
        dari <b>{{ $data->total() }}</b> data
    </div>
    <div class="pagination">
        {{ $data->appends(request()->query())->links() }}
    </div>
</div>
@endif

{{-- SCRIPT UNTUK HAPUS SEMUA --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnDeleteAll = document.getElementById('btn-delete-all');

    if (btnDeleteAll) {
        const deleteAllUrl = @json(route('admin.riwayat.delete-all'));
        console.log('🔗 Delete All URL:', deleteAllUrl);

        btnDeleteAll.addEventListener('click', function(e) {
            e.preventDefault();

            if (!confirm('⚠️ Yakin ingin menghapus SEMUA data? Tindakan ini tidak dapat dibatalkan!')) {
                return;
            }

            btnDeleteAll.disabled = true;
            btnDeleteAll.textContent = '⏳ Menghapus...';

            fetch(deleteAllUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Server error'); });
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Response:', data);
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Gagal menghapus data: ' + data.message);
                    btnDeleteAll.disabled = false;
                    btnDeleteAll.textContent = '🗑️ Hapus Semua';
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                alert('❌ Terjadi kesalahan: ' + error.message);
                btnDeleteAll.disabled = false;
                btnDeleteAll.textContent = '🗑️ Hapus Semua';
            });
        });
    }
});
</script>

@endsection