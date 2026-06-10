<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel sensor_readings menyimpan data setiap 15 menit (bukan setiap kiriman IoT).
     * Kolom nilai_fuzzy & deteksi disimpan langsung saat insert agar query riwayat
     * tidak perlu menghitung ulang Fuzzy Sugeno di sisi PHP.
     */
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->float('suhu');
            $table->float('kelembapan_udara');
            $table->float('kelembapan_tanah');

            // Hasil kalkulasi Fuzzy Sugeno — disimpan saat insert agar efisien
            $table->float('nilai_fuzzy')->nullable();
            $table->string('deteksi', 10)->nullable();   // 'HAMA' | 'WASPADA' | 'AMAN'

            // Gambar dari kamera IoT (ESP32-CAM) — nullable, tidak semua kiriman ada foto
            $table->string('image')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};