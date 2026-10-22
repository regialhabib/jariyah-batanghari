<?php

namespace App\Enums;

enum Role: string
{
    case AdminBpn = 'admin_bpn';
    case Bpn = 'bpn';
    case Kua = 'kua';
    case Nazir = 'nazir';

    public function label(): string
    {
        return match ($this) {
            self::AdminBpn => 'Admin BPN',
            self::Bpn => 'Petugas BPN',
            self::Kua => 'Petugas KUA',
            self::Nazir => 'Nazir',
        };
    }

    /**
     * Role yang bekerja di area admin (/admin).
     *
     * @return list<self>
     */
    public static function petugas(): array
    {
        return [self::AdminBpn, self::Bpn, self::Kua];
    }

    /**
     * Role BPN (admin maupun petugas) yang berwenang atas sertifikat.
     *
     * @return list<self>
     */
    public static function bpn(): array
    {
        return [self::AdminBpn, self::Bpn];
    }
}
