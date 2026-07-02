<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->string('deteksi_yolo')->nullable()->after('deteksi');
            $table->decimal('confidence_yolo', 5, 2)->nullable()->after('deteksi_yolo');
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropColumn(['deteksi_yolo', 'confidence_yolo']);
        });
    }
};