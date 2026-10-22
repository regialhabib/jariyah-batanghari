<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan key-value untuk konten publik (kontak, hero, tentang, sosial).
 * Baca lewat helper setting('kunci').
 *
 * @property int $id
 * @property string $kunci
 * @property string|null $nilai
 * @property string $grup
 */
#[Fillable(['kunci', 'nilai', 'grup'])]
class Pengaturan extends Model
{
    public const CACHE_KEY = 'pengaturan.semua';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * @return array<string, string|null>
     */
    public static function semua(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('nilai', 'kunci')->all());
    }

    public static function set(string $kunci, ?string $nilai, string $grup = 'umum'): void
    {
        static::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai, 'grup' => $grup]);
    }
}
