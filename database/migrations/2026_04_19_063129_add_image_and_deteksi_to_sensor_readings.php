<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up()
        {
            Schema::table('sensor_readings', function (Blueprint $table) {
                $table->string('image')->nullable();
                $table->string('deteksi')->nullable();
            });
        }

        public function down()
        {
            Schema::table('sensor_readings', function (Blueprint $table) {
                $table->dropColumn(['image','deteksi']);
            });
        }
};
