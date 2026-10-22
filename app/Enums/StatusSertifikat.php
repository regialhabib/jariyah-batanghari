<?php

namespace App\Enums;

enum StatusSertifikat: string
{
    case Belum = 'belum';
    case Proses = 'proses';
    case Sudah = 'sudah';

    public function label(): string
    {
        return match ($this) {
            self::Belum => 'Belum Bersertifikat',
            self::Proses => 'Dalam Proses',
            self::Sudah => 'Sudah Bersertifikat',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Belum => 'bg-amber-100 text-amber-800',
            self::Proses => 'bg-blue-100 text-blue-700',
            self::Sudah => 'bg-primary-100 text-primary-700',
        };
    }

    /**
     * Warna marker di peta (hex).
     */
    public function warnaPeta(): string
    {
        return match ($this) {
            self::Belum => '#F59E0B',
            self::Proses => '#3B82F6',
            self::Sudah => '#2E8B4E',
        };
    }
}
