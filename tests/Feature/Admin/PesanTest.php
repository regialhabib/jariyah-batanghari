<?php

namespace Tests\Feature\Admin;

use App\Models\Pesan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pesan Masuk (Fase 7): Admin BPN & Petugas BPN.
 */
class PesanTest extends TestCase
{
    use RefreshDatabase;

    private function pesan(array $atribut = []): Pesan
    {
        return Pesan::query()->create([
            'nama' => 'Pengirim', 'kontak' => 'pengirim@example.com', 'isi' => 'Halo, saya ingin bertanya.', ...$atribut,
        ]);
    }

    public function test_akses_hanya_untuk_bpn(): void
    {
        $this->pesan(['nama' => 'Budi Penanya']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.pesan'))->assertOk()->assertSee('Budi Penanya');
        $this->actingAs(User::factory()->bpn()->create())->get(route('admin.pesan'))->assertOk();
        $this->actingAs(User::factory()->kua()->create())->get(route('admin.pesan'))->assertForbidden();
    }

    public function test_membuka_pesan_menandai_dibaca_dan_filter_belum_dibaca(): void
    {
        $belum = $this->pesan(['nama' => 'Belum Dibaca']);
        $sudah = $this->pesan(['nama' => 'Sudah Dibaca']);
        $sudah->forceFill(['dibaca_at' => now()])->save();

        $this->actingAs(User::factory()->bpn()->create());

        $komponen = Livewire::test('pages::admin.pesan')
            ->set('belumDibaca', true)
            ->assertSee('Belum Dibaca')
            ->assertDontSee('Sudah Dibaca')
            ->call('buka', $belum->id)
            ->assertSet('dibukaId', $belum->id)
            ->assertSee('Halo, saya ingin bertanya.');

        $this->assertNotNull($belum->fresh()->dibaca_at);

        $komponen->call('tandaiBelumDibaca', $belum->id);
        $this->assertNull($belum->fresh()->dibaca_at);

        $komponen->call('hapus', $sudah->id);
        $this->assertModelMissing($sudah);
    }

    public function test_tautan_balas(): void
    {
        $this->actingAs(User::factory()->bpn()->create());
        $komponen = Livewire::test('pages::admin.pesan')->instance();

        $this->assertStringStartsWith('mailto:a@b.com', $komponen->tautanBalas(new Pesan(['kontak' => 'a@b.com'])));
        $this->assertSame('https://wa.me/6281234567890', $komponen->tautanBalas(new Pesan(['kontak' => '0812-3456-7890'])));
    }
}
