<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            // Kita cek secara aman, jika kolom belum ada di pgAdmin, langsung buat.
            if (!Schema::hasColumn('sensor_readings', 'nilai_fuzzy')) {
                $table->double('nilai_fuzzy')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            if (Schema::hasColumn('sensor_readings', 'nilai_fuzzy')) {
                $table->dropColumn('nilai_fuzzy');
            }
        });
    }
};