<?php

namespace Tests\Feature\Publik;

use App\Models\Pesan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Form Kontak publik: simpan pesan, honeypot, dan rate limit 3 kiriman / 10 menit / IP.
 */
class KontakTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiriman_valid_disimpan(): void
    {
        Livewire::test('pages::publik.kontak')
            ->set('nama', 'Siti Aminah')
            ->set('kontak', 'siti@example.com')
            ->set('pesan', 'Bagaimana cara mendaftarkan tanah wakaf masjid kami?')
            ->call('kirim')
            ->assertHasNoErrors()
            ->assertSet('terkirim', true)
            ->assertSet('nama', '')
            ->assertSee('Pesan Anda berhasil dikirim');

        $this->assertDatabaseHas('pesans', [
            'nama' => 'Siti Aminah',
            'kontak' => 'siti@example.com',
            'isi' => 'Bagaimana cara mendaftarkan tanah wakaf masjid kami?',
            'ip' => '127.0.0.1',
        ]);
    }

    public function test_nomor_hp_diterima_sebagai_kontak(): void
    {
        Livewire::test('pages::publik.kontak')
            ->set('nama', 'Budi Santoso')
            ->set('kontak', '0812-3456-7890')
            ->set('pesan', 'Mohon informasi jadwal pelayanan.')
            ->call('kirim')
            ->assertHasNoErrors();

        $this->assertSame(1, Pesan::query()->count());
    }

    public function test_validasi_wajib_dan_format_kontak(): void
    {
        Livewire::test('pages::publik.kontak')
            ->call('kirim')
            ->assertHasErrors(['nama' => 'required', 'kontak' => 'required', 'pesan' => 'required']);

        Livewire::test('pages::publik.kontak')
            ->set('nama', 'Budi')
            ->set('kontak', 'bukan kontak')
            ->set('pesan', 'Pendek')
            ->call('kirim')
            ->assertHasErrors(['kontak', 'pesan' => 'min']);

        $this->assertSame(0, Pesan::query()->count());
    }

    public function test_honeypot_terisi_tidak_menyimpan_apa_pun(): void
    {
        Livewire::test('pages::publik.kontak')
            ->set('nama', 'Bot Spam')
            ->set('kontak', 'bot@example.com')
            ->set('pesan', 'Promo murah sekali, klik tautan ini sekarang juga!')
            ->set('website', 'https://spam.example.com')
            ->call('kirim')
            ->assertHasNoErrors()
            ->assertSet('terkirim', true);

        $this->assertSame(0, Pesan::query()->count());
    }

    public function test_kiriman_keempat_dalam_10_menit_ditolak(): void
    {
        $komponen = Livewire::test('pages::publik.kontak');

        foreach (range(1, 3) as $i) {
            $komponen->set('nama', "Pengirim {$i}")
                ->set('kontak', "pengirim{$i}@example.com")
                ->set('pesan', "Pesan percobaan nomor {$i} untuk admin.")
                ->call('kirim')
                ->assertHasNoErrors();
        }

        $komponen->set('nama', 'Pengirim 4')
            ->set('kontak', 'pengirim4@example.com')
            ->set('pesan', 'Pesan percobaan nomor 4 untuk admin.')
            ->call('kirim')
            ->assertHasErrors('kirim')
            ->assertSet('terkirim', false)
            ->assertSee('terlalu banyak pesan');

        $this->assertSame(3, Pesan::query()->count());

        // Setelah jendela 10 menit berlalu, pengiriman kembali diizinkan.
        $this->travel(11)->minutes();

        $komponen->call('kirim')->assertHasNoErrors();
        $this->assertSame(4, Pesan::query()->count());
    }
}
