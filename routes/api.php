<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

Route::post('/sensor', [SensorController::class, 'store']); 
Route::get('/kamera/latest', [SensorController::class, 'kameraLatest'])->name('kamera.api');
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});