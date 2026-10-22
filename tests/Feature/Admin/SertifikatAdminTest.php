<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPengajuan;
use App\Enums\StatusPengajuan;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SertifikatAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_sertifikat_menampilkan_antrian_dan_tanah_bersertifikat(): void
    {
        $bpn = User::factory()->bpn()->create();
        $nazir = User::factory()->nazir()->create();
        $tanah = TanahWakaf::factory()->milik($nazir)->create(['nama' => 'Tanah Menunggu Terbit']);
        $menunggu = Pengajuan::factory()->diajukan()->jenis(JenisPengajuan::SertifikatBelum)->create([
            'nazir_id' => $nazir->id, 'tanah_wakaf_id' => $tanah->id, 'status' => StatusPengajuan::Terverifikasi,
        ]);
        TanahWakaf::factory()->bersertifikat()->create(['nama' => 'Makam Bersertifikat', 'nomor_sertifikat' => 'SRT-777']);
        TanahWakaf::factory()->create(['nama' => 'Mushola Belum']);

        $this->actingAs($bpn)->get(route('admin.sertifikat'))->assertOk();

        Livewire::test('pages::admin.sertifikat')
            ->assertSee('Tanah Menunggu Terbit')
            ->assertSee(route('admin.pengajuan.show', $menunggu))
            ->assertSee('Makam Bersertifikat')
            ->assertSee('SRT-777')
            ->assertDontSee('Mushola Belum')
            ->set('q', 'SRT-777')
            ->assertSee('Makam Bersertifikat')
            ->set('q', 'tidak-ada')
            ->assertDontSee('Makam Bersertifikat');
    }

    public function test_kua_tidak_bisa_membuka_halaman_sertifikat(): void
    {
        $kua = User::factory()->kua(Kecamatan::factory()->create())->create();

        $this->actingAs($kua)->get(route('admin.sertifikat'))->assertForbidden();
        Livewire::test('pages::admin.sertifikat')->assertForbidden();
    }
}
