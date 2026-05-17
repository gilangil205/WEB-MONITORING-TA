<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

// 1. Arahkan halaman utama ke dashboard (atau login jika belum masuk)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// 2. Bungkus semua route monitoring dengan Middleware Auth
// Artinya: Orang luar tidak bisa melihat data sebelum login
Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('/dashboard', [SensorController::class, 'index'])->name('dashboard');
    Route::get('/monitoring', [SensorController::class, 'monitoring'])->name('monitoring');
    Route::get('/prediksi', [SensorController::class, 'prediksi'])->name('prediksi');
    Route::get('/riwayat', [SensorController::class, 'riwayat'])->name('riwayat');
    Route::get('/kamera', [SensorController::class, 'kamera'])->name('kamera');
    
    // Route untuk tombol tambah data manual
    Route::post('/manual', [SensorController::class, 'manual'])->name('manual');
});

// 3. Route API untuk Alat/IoT (Harus di luar Auth agar alat bisa kirim data)
Route::post('/api/sensor', [SensorController::class, 'store']);

// 4. Baris ini biasanya ditambahkan otomatis oleh Breeze, biarkan saja:
require __DIR__.'/auth.php';