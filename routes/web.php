<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [SensorController::class, 'index'])->name('dashboard');
    Route::get('/prediksi',   [SensorController::class, 'prediksi'])->name('prediksi');
    Route::get('/riwayat',    [SensorController::class, 'riwayat'])->name('riwayat');
    Route::get('/kamera',     [SensorController::class, 'kamera'])->name('kamera');
    Route::get('/live-data',  [SensorController::class, 'liveData'])->name('live-data');
    Route::post('/manual',    [SensorController::class, 'manual'])->name('manual');

    // ============================================================
    // RUTE NOTIFIKASI
    // ============================================================
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [SensorController::class, 'adminDashboard'])->name('dashboard');

    Route::post('/threshold/update', [SensorController::class, 'updateThreshold'])->name('threshold.update');
    Route::post('/threshold/reset',  [SensorController::class, 'resetThreshold'])->name('threshold.reset');

    Route::post('/users',           [SensorController::class, 'storeUser'])->name('users.store');
    Route::delete('/users/{user}',  [SensorController::class, 'deleteUser'])->name('users.delete');

    Route::get('/riwayat', [SensorController::class, 'adminRiwayat'])->name('riwayat');
    Route::delete('/riwayat/all', [SensorController::class, 'adminDestroyAllRiwayat'])->name('riwayat.delete-all');
    Route::delete('/riwayat/{id}', [SensorController::class, 'adminDestroyRiwayat'])->name('riwayat.delete');
});


if (app()->environment('local', 'staging')) {
    Route::get('/debug/fuzzy', [SensorController::class, 'debugFuzzy'])->name('debug.fuzzy');
}

require __DIR__ . '/auth.php';