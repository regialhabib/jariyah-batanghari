<?php

namespace App\Enums;

enum JenisPenggunaan: string
{
    case Masjid = 'masjid';
    case Mushola = 'mushola';
    case Pesantren = 'pesantren';
    case Sekolah = 'sekolah';
    case Makam = 'makam';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Masjid => 'Masjid',
            self::Mushola => 'Mushola',
            self::Pesantren => 'Pondok Pesantren',
            self::Sekolah => 'Sekolah/Madrasah',
            self::Makam => 'Makam',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * @return array<string, string> value => label, untuk dropdown.
     */
    public static function opsi(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
