<?php
// ============================================================
// FILE 2: database/migrations/2026_06_09_000002_create_threshold_settings_table.php
// ============================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tabel threshold_settings — menyimpan batas suhu untuk kategori hama.
     *
     * Struktur sengaja dibuat simpel (key-value) sesuai kebutuhan revisian:
     * hanya mengatur batas min/max suhu untuk menentukan status hama.
     *
     * Baris default:
     *   suhu_min_hama    → 30 °C  (suhu ≥ nilai ini masuk zona berisiko)
     *   suhu_max_aman    → 25 °C  (suhu ≤ nilai ini dianggap aman)
     *   threshold_hama   → 0.70   (nilai fuzzy ≥ ini → HAMA)
     *   threshold_waspada→ 0.45   (nilai fuzzy ≥ ini → WASPADA)
     */
    public function up(): void
    {
        Schema::create('threshold_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();        // nama parameter
            $table->float('value');                 // nilai saat ini
            $table->float('min_input');             // batas bawah yang boleh diinput
            $table->float('max_input');             // batas atas yang boleh diinput
            $table->string('satuan', 10)->default('');  // '°C', '%', atau ''
            $table->string('label');                // label UI
            $table->text('keterangan')->nullable(); // penjelasan singkat
            $table->timestamps();
        });

        // ── Seed nilai default ────────────────────────────────────
        $now = now();
        DB::table('threshold_settings')->insert([
            [
                'key'         => 'suhu_panas_min',
                'value'       => 30,
                'min_input'   => 20,
                'max_input'   => 45,
                'satuan'      => '°C',
                'label'       => 'Suhu Minimum Zona Panas',
                'keterangan'  => 'Suhu ≥ nilai ini dianggap zona panas (berisiko hama tinggi).',
                'created_at'  => $now, 'updated_at' => $now,
            ],
            [
                'key'         => 'suhu_dingin_max',
                'value'       => 25,
                'min_input'   => 10,
                'max_input'   => 30,
                'satuan'      => '°C',
                'label'       => 'Suhu Maksimum Zona Dingin',
                'keterangan'  => 'Suhu ≤ nilai ini dianggap zona dingin (risiko hama rendah).',
                'created_at'  => $now, 'updated_at' => $now,
            ],
            [
                'key'         => 'suhu_hangat_min',
                'value'       => 22,
                'min_input'   => 10,
                'max_input'   => 35,
                'satuan'      => '°C',
                'label'       => 'Suhu Minimum Zona Hangat',
                'keterangan'  => 'Batas bawah zona hangat — mulai perlu dipantau.',
                'created_at'  => $now, 'updated_at' => $now,
            ],
            [
                'key'         => 'suhu_hangat_max',
                'value'       => 32,
                'min_input'   => 20,
                'max_input'   => 45,
                'satuan'      => '°C',
                'label'       => 'Suhu Maksimum Zona Hangat',
                'keterangan'  => 'Batas atas zona hangat — di atas ini masuk zona panas.',
                'created_at'  => $now, 'updated_at' => $now,
            ],
            [
                'key'         => 'threshold_hama',
                'value'       => 0.70,
                'min_input'   => 0.50,
                'max_input'   => 1.00,
                'satuan'      => '',
                'label'       => 'Nilai Fuzzy Batas HAMA',
                'keterangan'  => 'Nilai output Fuzzy Sugeno ≥ nilai ini → status HAMA.',
                'created_at'  => $now, 'updated_at' => $now,
            ],
            [
                'key'         => 'threshold_waspada',
                'value'       => 0.45,
                'min_input'   => 0.10,
                'max_input'   => 0.69,
                'satuan'      => '',
                'label'       => 'Nilai Fuzzy Batas WASPADA',
                'keterangan'  => 'Nilai output Fuzzy Sugeno ≥ nilai ini (dan < batas HAMA) → WASPADA.',
                'created_at'  => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('threshold_settings');
    }
};