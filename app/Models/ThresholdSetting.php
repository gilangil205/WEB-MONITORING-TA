<?php
// ============================================================
// FILE 3: app/Models/ThresholdSetting.php
// ============================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ThresholdSetting extends Model
{
    protected $table = 'threshold_settings';

    protected $fillable = [
        'key', 'value', 'min_input', 'max_input', 'satuan', 'label', 'keterangan',
    ];

    protected $casts = [
        'value'     => 'float',
        'min_input' => 'float',
        'max_input' => 'float',
    ];

    const CACHE_KEY = 'threshold_settings_all';
    const CACHE_TTL = 3600; // 1 jam

    /**
     * Ambil semua setting sebagai array [ key => value ].
     * Di-cache 1 jam — di-clear otomatis saat admin menyimpan perubahan.
     */
    public static function getAllValues(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::pluck('value', 'key')->toArray();
        });
    }

    /** Ambil satu nilai berdasarkan key, dengan fallback jika belum ada. */
    public static function getValue(string $key, float $default = 0): float
    {
        return self::getAllValues()[$key] ?? $default;
    }

    /** Hapus cache setelah admin menyimpan — agar perubahan langsung berlaku. */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}