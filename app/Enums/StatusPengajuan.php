<?php

namespace App\Enums;

enum StatusPengajuan: string
{
    case Draf = 'draf';
    case Diajukan = 'diajukan';
    case PerluPerbaikan = 'perlu_perbaikan';
    case AiwDisahkan = 'aiw_disahkan';
    case Terverifikasi = 'terverifikasi';
    case SertifikatTerbit = 'sertifikat_terbit';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Diajukan => 'Diajukan',
            self::PerluPerbaikan => 'Perlu Perbaikan',
            self::AiwDisahkan => 'AIW Disahkan',
            self::Terverifikasi => 'Terverifikasi',
            self::SertifikatTerbit => 'Sertifikat Terbit',
            self::Ditolak => 'Ditolak',
        };
    }

    /**
     * Kelas Tailwind untuk badge status.
     */
    public function warna(): string
    {
        return match ($this) {
            self::Draf => 'bg-zinc-100 text-zinc-700',
            self::Diajukan => 'bg-blue-100 text-blue-700',
            self::PerluPerbaikan => 'bg-amber-100 text-amber-800',
            self::AiwDisahkan, self::SertifikatTerbit => 'bg-primary-100 text-primary-700',
            self::Terverifikasi => 'bg-teal-100 text-teal-700',
            self::Ditolak => 'bg-red-100 text-red-700',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::AiwDisahkan, self::SertifikatTerbit, self::Ditolak], true);
    }

    /**
     * Pengajuan masih berjalan (sudah dikirim, belum mencapai status akhir).
     */
    public function isAktif(): bool
    {
        return ! $this->isFinal() && $this !== self::Draf;
    }

    /**
     * Nazir masih boleh mengubah isi pengajuan.
     */
    public function bisaDiubahNazir(): bool
    {
        return in_array($this, [self::Draf, self::PerluPerbaikan], true);
    }

    /**
     * Transisi status yang sah per jenis pengajuan (lihat docs/03-alur-pengajuan.md).
     */
    public function bisaKe(self $tujuan, JenisPengajuan $jenis): bool
    {
        return in_array($tujuan, $this->tujuanSah($jenis), true);
    }

    /**
     * @return list<self>
     */
    public function tujuanSah(JenisPengajuan $jenis): array
    {
        if ($jenis === JenisPengajuan::Aiw) {
            return match ($this) {
                self::Draf, self::PerluPerbaikan => [self::Diajukan],
                self::Diajukan => [self::PerluPerbaikan, self::AiwDisahkan, self::Ditolak],
                default => [],
            };
        }

        return match ($this) {
            self::Draf, self::PerluPerbaikan => [self::Diajukan],
            self::Diajukan => [self::PerluPerbaikan, self::Terverifikasi, self::Ditolak],
            self::Terverifikasi => [self::SertifikatTerbit, self::Ditolak],
            default => [],
        };
    }
}
