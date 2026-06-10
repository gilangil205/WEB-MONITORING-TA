<?php
// ============================================================
// FILE 1: database/migrations/2026_06_09_000001_add_role_to_users_table.php
// ============================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom 'role' ke tabel users.
     * Nilai: 'admin' | 'user'
     * Default 'user' → semua akun lama otomatis jadi user biasa.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 10)->default('user')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};