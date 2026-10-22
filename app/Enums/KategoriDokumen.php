<?php

namespace App\Enums;

enum KategoriDokumen: string
{
    case Persyaratan = 'persyaratan';
    case HasilAiw = 'hasil_aiw';
    case HasilSertifikat = 'hasil_sertifikat';

    public function label(): string
    {
        return match ($this) {
            self::Persyaratan => 'Dokumen Persyaratan',
            self::HasilAiw => 'Scan Akta Ikrar Wakaf',
            self::HasilSertifikat => 'Scan Sertifikat Wakaf',
        };
    }
}
