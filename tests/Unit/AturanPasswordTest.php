<?php

namespace Tests\Unit;

use App\Support\AturanPassword;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\TestCase;

class AturanPasswordTest extends TestCase
{
    public function test_aturan_ketat_produksi_tercermin_di_passwordrules(): void
    {
        $aturan = Password::min(12)->mixedCase()->letters()->numbers()->symbols();

        $this->assertSame(
            'minlength: 12; required: lower; required: upper; required: digit; required: special;',
            AturanPassword::keString($aturan),
        );
    }

    public function test_aturan_minimal_hanya_berisi_panjang(): void
    {
        $this->assertSame('minlength: 8;', AturanPassword::keString(Password::min(8)));
    }

    public function test_huruf_tanpa_campuran_besar_kecil_dan_batas_maksimum(): void
    {
        $this->assertSame(
            'minlength: 8; maxlength: 64; required: lower, upper;',
            AturanPassword::keString(Password::min(8)->max(64)->letters()),
        );
    }
}
