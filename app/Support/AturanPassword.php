<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Menyusun nilai atribut HTML `passwordrules` dari aturan password aplikasi,
 * agar saran password otomatis di peramban memenuhi aturan tersebut.
 */
class AturanPassword
{
    public static function keString(?Password $aturan = null): string
    {
        $terapan = ($aturan ?? Password::defaults())->appliedRules();

        $bagian = ["minlength: {$terapan['min']}"];

        if ($terapan['max'] !== null) {
            $bagian[] = "maxlength: {$terapan['max']}";
        }

        if ($terapan['mixedCase']) {
            $bagian[] = 'required: lower';
            $bagian[] = 'required: upper';
        } elseif ($terapan['letters']) {
            $bagian[] = 'required: lower, upper';
        }

        if ($terapan['numbers']) {
            $bagian[] = 'required: digit';
        }

        if ($terapan['symbols']) {
            $bagian[] = 'required: special';
        }

        return implode('; ', $bagian).';';
    }
}
