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
            // Cek dulu apakah kolom nilai_fuzzy sudah ada atau belum untuk menghindari error
            if (!Schema::hasColumn('sensor_readings', 'nilai_fuzzy')) {
                $table->float('nilai_fuzzy')->nullable()->after('kelembapan_tanah');
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