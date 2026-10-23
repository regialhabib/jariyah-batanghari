<?php

namespace App\Rules;

use App\Models\TanahWakaf;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Nomor AIW / nomor sertifikat tidak boleh sudah dipakai tanah wakaf lain.
 * Nomor tetap diisi manual oleh petugas (nomor resmi dari dokumen fisik), aturan ini mencegah salah ketik ganda.
 */
class NomorBelumDipakai implements ValidationRule
{
    /**
     * @param  'nomor_aiw'|'nomor_sertifikat'  $kolom
     * @param  int|null  $kecualiTanahId  tanah yang sedang diubah, boleh memakai nomornya sendiri
     */
    public function __construct(
        private string $kolom,
        private ?int $kecualiTanahId = null,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $pemakai = TanahWakaf::pemakaiNomor($this->kolom, $value, $this->kecualiTanahId);

        if ($pemakai) {
            $fail(':Attribute ini sudah dipakai oleh '.$pemakai->kode.' · '.$pemakai->nama.'.');
        }
    }
}
