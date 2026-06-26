<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ============================================================
     * JALANKAN: php artisan migrate
     * ============================================================
     *
     * Migration ini mengganti SEMUA key threshold lama menjadi
     * 9 key zona baru (3 zona per parameter):
     *
     *   suhu_aman    → suhu ≤ ini : AMAN      (default 22°C)
     *   suhu_waspada → suhu antara : WASPADA  (default 28°C)
     *   suhu_hama    → suhu ≥ ini : HAMA      (default 32°C)
     *
     *   udara_aman / udara_waspada / udara_hama  (default 60/75/85 %)
     *   tanah_aman / tanah_waspada / tanah_hama  (default 55/68/80 %)
     *
     * Migration ini AMAN dijalankan dalam kondisi DB apapun:
     *   - Masih key lama (suhu_panas_min, suhu_dingin_max, dll)
     *   - Sudah key min/max (suhu_min, suhu_max, dll)
     *   - Sudah key zona baru (tidak mengubah apa-apa)
     *
     * Key yang TIDAK disentuh: threshold_hama & threshold_waspada
     * ============================================================
     */
    public function up(): void
    {
        $now = now();

        // Hapus SEMUA key lama yang tidak dipakai lagi
        DB::table('threshold_settings')->whereIn('key', [
            'suhu_panas_min', 'suhu_dingin_max', 'suhu_hangat_min', 'suhu_hangat_max',
            'suhu_min', 'suhu_max',
            'udara_min', 'udara_max',
            'tanah_min', 'tanah_max',
        ])->delete();

        // Insert 9 key zona baru (hanya jika belum ada)
        $baru = [
            // SUHU UDARA
            ['key'=>'suhu_aman',    'value'=>22, 'min_input'=>5,  'max_input'=>40,  'satuan'=>'°C',
             'label'=>'Suhu — Batas Zona AMAN',    'keterangan'=>'Suhu ≤ nilai ini = TIDAK ADA HAMA (kondisi dingin).'],
            ['key'=>'suhu_waspada', 'value'=>28, 'min_input'=>10, 'max_input'=>42,  'satuan'=>'°C',
             'label'=>'Suhu — Batas Zona WASPADA', 'keterangan'=>'Suhu antara AMAN–WASPADA = POTENSI HAMA (kondisi hangat).'],
            ['key'=>'suhu_hama',    'value'=>32, 'min_input'=>15, 'max_input'=>50,  'satuan'=>'°C',
             'label'=>'Suhu — Batas Zona HAMA',    'keterangan'=>'Suhu ≥ nilai ini = ADA HAMA (kondisi panas, risiko tinggi).'],
            // KELEMBAPAN UDARA
            ['key'=>'udara_aman',    'value'=>60, 'min_input'=>0, 'max_input'=>100, 'satuan'=>'%',
             'label'=>'Kel. Udara — Batas Zona AMAN',    'keterangan'=>'Kelembapan udara ≤ nilai ini = TIDAK ADA HAMA (kering).'],
            ['key'=>'udara_waspada', 'value'=>75, 'min_input'=>0, 'max_input'=>100, 'satuan'=>'%',
             'label'=>'Kel. Udara — Batas Zona WASPADA', 'keterangan'=>'Kelembapan udara antara AMAN–WASPADA = POTENSI HAMA.'],
            ['key'=>'udara_hama',    'value'=>85, 'min_input'=>0, 'max_input'=>100, 'satuan'=>'%',
             'label'=>'Kel. Udara — Batas Zona HAMA',    'keterangan'=>'Kelembapan udara ≥ nilai ini = ADA HAMA (lembap, risiko tinggi).'],
            // KELEMBAPAN TANAH
            ['key'=>'tanah_aman',    'value'=>55, 'min_input'=>0, 'max_input'=>100, 'satuan'=>'%',
             'label'=>'Kel. Tanah — Batas Zona AMAN',    'keterangan'=>'Kelembapan tanah ≤ nilai ini = TIDAK ADA HAMA (kering).'],
            ['key'=>'tanah_waspada', 'value'=>68, 'min_input'=>0, 'max_input'=>100, 'satuan'=>'%',
             'label'=>'Kel. Tanah — Batas Zona WASPADA', 'keterangan'=>'Kelembapan tanah antara AMAN–WASPADA = POTENSI HAMA.'],
            ['key'=>'tanah_hama',    'value'=>80, 'min_input'=>0, 'max_input'=>100, 'satuan'=>'%',
             'label'=>'Kel. Tanah — Batas Zona HAMA',    'keterangan'=>'Kelembapan tanah ≥ nilai ini = ADA HAMA (lembap, risiko tinggi).'],
        ];

        foreach ($baru as $row) {
            if (!DB::table('threshold_settings')->where('key', $row['key'])->exists()) {
                DB::table('threshold_settings')->insert(array_merge($row, [
                    'created_at' => $now, 'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('threshold_settings')->whereIn('key', [
            'suhu_aman','suhu_waspada','suhu_hama',
            'udara_aman','udara_waspada','udara_hama',
            'tanah_aman','tanah_waspada','tanah_hama',
        ])->delete();
    }
};