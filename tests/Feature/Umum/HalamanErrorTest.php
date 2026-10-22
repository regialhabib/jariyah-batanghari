<?php

namespace Tests\Feature\Umum;

use Tests\TestCase;

class HalamanErrorTest extends TestCase
{
    public function test_halaman_404_berbahasa_indonesia(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Ke Beranda');
    }

    public function test_semua_halaman_error_bisa_dirender(): void
    {
        foreach ([403, 404, 419, 429, 500, 503] as $kode) {
            $html = view("errors.$kode", ['exception' => new \Exception])->render();

            $this->assertStringContainsString((string) $kode, $html);
        }
    }
}
