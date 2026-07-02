@extends('layouts.app')

@section('title','Riwayat Data Sensor')

@section('content')

<div class="section">

    <h2 style="margin-bottom:16px;">📋 Riwayat Data Sensor</h2>

    {{-- FORM FILTER --}}
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; align-items:center;">

        {{-- Filter Waktu --}}
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

        {{-- Filter Deteksi --}}
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

        {{-- Tombol Reset --}}
        <div style="align-self:flex-end;">
            <a href="{{ route('riwayat') }}"
               style="display:inline-block; padding:8px 14px; border-radius:8px; background:#f1f5f9; color:#475569; text-decoration:none; font-size:13px; border:1px solid #ddd;">
                🔄 Reset
            </a>
        </div>

    </form>

    {{-- RINGKASAN --}}
    @if($data->total() > 0)
    <div style="background:#f8fafc; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; gap:24px; flex-wrap:wrap; font-size:13px; color:#475569;">
        <span>📊 Total data: <b>{{ $data->total() }}</b></span>
        <span>🔴 HAMA: <b>{{ $data->getCollection()->where('deteksi','HAMA')->count() }}</b></span>
        <span>🟡 WASPADA: <b>{{ $data->getCollection()->where('deteksi','WASPADA')->count() }}</b></span>
        <span>🟢 AMAN: <b>{{ $data->getCollection()->where('deteksi','AMAN')->count() }}</b></span>
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
                </tr>
            </thead>

            <tbody>
                @forelse($data as $i => $item)
                <tr>

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
                            <span class="
                                {{ $item->deteksi_yolo == 'Tikus Terdeteksi' ? 'status-high' : 'status-low' }}">
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

                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center; padding:30px; color:#94a3b8;">
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

</div>

@endsection