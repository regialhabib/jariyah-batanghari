<?php

use App\Models\Pengaturan;

if (! function_exists('setting')) {
    /**
     * Ambil nilai pengaturan konten (tabel pengaturans, di-cache).
     */
    function setting(string $kunci, ?string $default = null): ?string
    {
        try {
            return Pengaturan::semua()[$kunci] ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }
}

if (! function_exists('setting_list')) {
    /**
     * Ambil pengaturan berformat JSON list (mis. daftar manfaat).
     *
     * @return list<string>
     */
    function setting_list(string $kunci): array
    {
        $nilai = json_decode((string) setting($kunci, '[]'), true);

        return is_array($nilai) ? array_values($nilai) : [];
    }
}

if (! function_exists('format_luas')) {
    function format_luas(float|int|string|null $m2): string
    {
        return number_format((float) $m2, 0, ',', '.').' m²';
    }
}
