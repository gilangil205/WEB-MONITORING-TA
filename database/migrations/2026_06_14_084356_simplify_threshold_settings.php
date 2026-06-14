<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migration ini WAJIB dijalankan: `php artisan migrate`
     *
     * Menyederhanakan threshold_settings menjadi 6 baris saja, sesuai
     * desain admin (Suhu Udara, Kelembapan Udara, Kelembapan Tanah):
     *
     *   suhu_min  / suhu_max   → default 22 - 30 °C
     *   udara_min / udara_max  → default 60 - 80 %
     *   tanah_min / tanah_max  → default 55 - 75 %
     *
     * Baris suhu_dingin_max & suhu_panas_min DIHAPUS — tidak dipakai lagi
     * oleh fuzzySugeno() versi baru (zona dingin/hangat/panas dihitung
     * otomatis dari suhu_min/suhu_max dengan lebar transisi tetap).
     *
     * threshold_hama & threshold_waspada TETAP ADA (tidak disentuh) —
     * dipakai internal oleh getStatus(), tidak ditampilkan di UI admin.
     */
    public function up(): void
    {
        $now = now();

        // Ganti suhu_hangat_min → suhu_min (22)
        if (DB::table('threshold_settings')->where('key', 'suhu_hangat_min')->exists()) {
            DB::table('threshold_settings')->where('key', 'suhu_hangat_min')->update([
                'key'        => 'suhu_min',
                'value'      => 22,
                'min_input'  => 10,
                'max_input'  => 35,
                'satuan'     => '°C',
                'label'      => 'Suhu Udara Minimum Ideal',
                'keterangan' => 'Suhu di bawah nilai ini mulai dianggap dingin.',
                'updated_at' => $now,
            ]);
        }

        // Ganti suhu_hangat_max → suhu_max (30)
        if (DB::table('threshold_settings')->where('key', 'suhu_hangat_max')->exists()) {
            DB::table('threshold_settings')->where('key', 'suhu_hangat_max')->update([
                'key'        => 'suhu_max',
                'value'      => 30,
                'min_input'  => 15,
                'max_input'  => 45,
                'satuan'     => '°C',
                'label'      => 'Suhu Udara Maksimum Ideal',
                'keterangan' => 'Suhu di atas nilai ini mulai dianggap panas (risiko hama naik).',
                'updated_at' => $now,
            ]);
        }

        // Hapus baris yang tidak dipakai lagi
        DB::table('threshold_settings')
            ->whereIn('key', ['suhu_dingin_max', 'suhu_panas_min'])
            ->delete();

        // Tambah udara_min / udara_max / tanah_min / tanah_max jika belum ada
        $toInsert = [
            [
                'key' => 'udara_min', 'value' => 60,
                'min_input' => 0, 'max_input' => 100, 'satuan' => '%',
                'label' => 'Kelembapan Udara Minimum Ideal',
                'keterangan' => 'Kelembapan udara di bawah nilai ini mulai dianggap kering.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'key' => 'udara_max', 'value' => 80,
                'min_input' => 0, 'max_input' => 100, 'satuan' => '%',
                'label' => 'Kelembapan Udara Maksimum Ideal',
                'keterangan' => 'Kelembapan udara di atas nilai ini mulai dianggap lembap (risiko hama naik).',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'key' => 'tanah_min', 'value' => 55,
                'min_input' => 0, 'max_input' => 100, 'satuan' => '%',
                'label' => 'Kelembapan Tanah Minimum Ideal',
                'keterangan' => 'Kelembapan tanah di bawah nilai ini mulai dianggap kering.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'key' => 'tanah_max', 'value' => 75,
                'min_input' => 0, 'max_input' => 100, 'satuan' => '%',
                'label' => 'Kelembapan Tanah Maksimum Ideal',
                'keterangan' => 'Kelembapan tanah di atas nilai ini mulai dianggap lembap (risiko hama naik).',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ];

        foreach ($toInsert as $row) {
            if (!DB::table('threshold_settings')->where('key', $row['key'])->exists()) {
                DB::table('threshold_settings')->insert($row);
            }
        }

        // Jaga-jaga: jika suhu_min/suhu_max masih belum ada sama sekali
        // (misal baris suhu_hangat_min/max sudah pernah dihapus manual),
        // insert manual dengan default.
        if (!DB::table('threshold_settings')->where('key', 'suhu_min')->exists()) {
            DB::table('threshold_settings')->insert([
                'key' => 'suhu_min', 'value' => 22,
                'min_input' => 10, 'max_input' => 35, 'satuan' => '°C',
                'label' => 'Suhu Udara Minimum Ideal',
                'keterangan' => 'Suhu di bawah nilai ini mulai dianggap dingin.',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        if (!DB::table('threshold_settings')->where('key', 'suhu_max')->exists()) {
            DB::table('threshold_settings')->insert([
                'key' => 'suhu_max', 'value' => 30,
                'min_input' => 15, 'max_input' => 45, 'satuan' => '°C',
                'label' => 'Suhu Udara Maksimum Ideal',
                'keterangan' => 'Suhu di atas nilai ini mulai dianggap panas (risiko hama naik).',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('threshold_settings')
            ->whereIn('key', ['udara_min', 'udara_max', 'tanah_min', 'tanah_max'])
            ->delete();

        DB::table('threshold_settings')->where('key', 'suhu_min')->update(['key' => 'suhu_hangat_min']);
        DB::table('threshold_settings')->where('key', 'suhu_max')->update(['key' => 'suhu_hangat_max']);
    }
};