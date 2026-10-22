<?php

namespace App\Enums;

enum JenisPengajuan: string
{
    case Aiw = 'aiw';
    case SertifikatBersertifikat = 'sertifikat_bersertifikat';
    case SertifikatBelum = 'sertifikat_belum';

    public function label(): string
    {
        return match ($this) {
            self::Aiw => 'Pembuatan AIW di KUA Kecamatan',
            self::SertifikatBersertifikat => 'Sertifikat Wakaf (Tanah Sudah Bersertifikat)',
            self::SertifikatBelum => 'Sertifikat Wakaf (Tanah Belum Bersertifikat)',
        };
    }

    public function labelSingkat(): string
    {
        return match ($this) {
            self::Aiw => 'Pembuatan AIW',
            self::SertifikatBersertifikat => 'Sertifikat (Sudah Bersertifikat)',
            self::SertifikatBelum => 'Sertifikat (Belum Bersertifikat)',
        };
    }

    /**
     * Role yang berwenang memeriksa pengajuan jenis ini.
     *
     * @return list<Role>
     */
    public function pemeriksa(): array
    {
        return match ($this) {
            self::Aiw => [Role::Kua],
            self::SertifikatBersertifikat, self::SertifikatBelum => Role::bpn(),
        };
    }

    public function isSertifikat(): bool
    {
        return $this !== self::Aiw;
    }
}
