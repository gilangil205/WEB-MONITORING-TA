<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

// ============================================================
// 1. HALAMAN UTAMA / REDIRECT
// ============================================================
Route::get('/', function () {
    return redirect()->route('dashboard');
});


// ============================================================
// 2. RUTE UNTUK SEMUA PENGGUNA TERAUTENTIKASI (USER & ADMIN)
// ============================================================
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [SensorController::class, 'index'])->name('dashboard');
    Route::get('/prediksi',   [SensorController::class, 'prediksi'])->name('prediksi');
    Route::get('/riwayat',    [SensorController::class, 'riwayat'])->name('riwayat');
    Route::get('/kamera',     [SensorController::class, 'kamera'])->name('kamera');

    // Endpoint JSON untuk Fetch API
    Route::get('/live-data',  [SensorController::class, 'liveData'])->name('live-data');

    // Tombol tambah data manual
    Route::post('/manual',    [SensorController::class, 'manual'])->name('manual');
});


// ============================================================
// Pastikan penulisan middleware menggunakan array ['auth', 'admin'] agar tidak error closure
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Halaman Utama Dashboard Admin
    Route::get('/dashboard', [SensorController::class, 'adminDashboard'])->name('dashboard');
    
    // Rute Pengaturan Threshold Suhu
    Route::post('/threshold/update', [SensorController::class, 'updateThreshold'])->name('threshold.update');
    Route::post('/threshold/reset', [SensorController::class, 'resetThreshold'])->name('threshold.reset');
    
    // Rute Manajemen Pengguna (User)
    Route::post('/users', [SensorController::class, 'storeUser'])->name('users.store');
    Route::delete('/users/{user}', [SensorController::class, 'deleteUser'])->name('users.delete');
    
});

// ============================================================
// 4. ENDPOINT API PERANGKAT IOT & PENDUKUNG (TANPA AUTH)
// ============================================================
Route::post('/api/sensor', [SensorController::class, 'store']);
Route::get('/api/kamera/latest', [SensorController::class, 'kameraLatest'])->name('kamera.api');


// ============================================================
// 5. RUTE AUTENTIKASI BAWAAN BREEZE
// ============================================================
require __DIR__ . '/auth.php';