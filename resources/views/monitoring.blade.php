@extends('layouts.app')

@section('title', 'Monitoring Sensor')

@section('content')

<div class="header">
    <h1 style="font-weight:600;">Monitoring Sensor Real-Time</h1>
    <p style="color:#64748b; margin-top:6px;">
        Data parameter lingkungan dari perangkat IoT secara langsung
    </p>
</div>

<div class="section">
    <h3 style="margin-bottom:15px;">Grafik Parameter Lingkungan</h3>
    <canvas id="multiChart"></canvas>
</div>

<div class="section">
    <h3 style="margin-bottom:20px;">Data Sensor Terbaru</h3>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Suhu (°C)</th>
                    <th>Kelembapan Udara (%)</th>
                    <th>Kelembapan Tanah (%)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>10:30</td>
                    <td>29</td>
                    <td>78</td>
                    <td>65</td>
                </tr>
                <tr>
                    <td>10:25</td>
                    <td>28</td>
                    <td>76</td>
                    <td>63</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<script>
const ctx = document.getElementById('multiChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['08:00','10:00','12:00','14:00','16:00'],
        datasets: [
            {
                label: 'Suhu (°C)',
                data: [27,28,30,29,28],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.15)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Kelembapan Udara (%)',
                data: [75,78,80,77,76],
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34,197,94,0.15)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true
    }
});
</script>

@endsection