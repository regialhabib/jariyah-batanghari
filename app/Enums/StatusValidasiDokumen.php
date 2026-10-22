<?php

namespace App\Enums;

enum StatusValidasiDokumen: string
{
    case Menunggu = 'menunggu';
    case Valid = 'valid';
    case TidakValid = 'tidak_valid';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Pemeriksaan',
            self::Valid => 'Valid',
            self::TidakValid => 'Tidak Valid',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Menunggu => 'bg-zinc-100 text-zinc-700',
            self::Valid => 'bg-primary-100 text-primary-700',
            self::TidakValid => 'bg-red-100 text-red-700',
        };
    }
}
