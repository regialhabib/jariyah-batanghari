<?php

namespace Tests\Feature\Peta;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HalamanPetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_peta_wakaf_tampil_dengan_filter_dan_informasi(): void
    {
        $kecamatan = Kecamatan::factory()->create(['nama' => 'Muara Bulian']);
        TanahWakaf::factory()->count(2)->create(['desa_id' => Desa::factory()->for($kecamatan)]);
        TanahWakaf::factory()->create();
        TanahWakaf::factory()->tanpaAiw()->create();

        $this->get(route('peta'))
            ->assertOk()
            ->assertSee('Filter Pencarian')
            ->assertSee('Muara Bulian')
            ->assertSee('Terapkan')
            ->assertSee('Klik pada titik untuk melihat detail informasi.')
            ->assertSee('x-data="petaWakaf(', false)
            ->assertSee('3 <span class="text-sm font-semibold">Bidang</span>', false)
            ->assertSee('2 Kecamatan, 3 Desa/Kelurahan');
    }

    public function test_terapkan_filter_mengirim_event_dan_memperbarui_ringkasan(): void
    {
        $kecamatan = Kecamatan::factory()->create();
        TanahWakaf::factory()->create(['desa_id' => Desa::factory()->for($kecamatan), 'jenis_penggunaan' => JenisPenggunaan::Masjid]);
        TanahWakaf::factory()->create(['desa_id' => Desa::factory()->for($kecamatan), 'jenis_penggunaan' => JenisPenggunaan::Makam]);
        TanahWakaf::factory()->create(['jenis_penggunaan' => JenisPenggunaan::Masjid]);

        Livewire::test('pages::publik.peta-wakaf')
            ->assertSee('2 Kecamatan, 3 Desa/Kelurahan')
            ->set('kecamatan', (string) $kecamatan->id)
            ->set('jenis', 'masjid')
            ->call('terapkan')
            ->assertDispatched('peta-filter', filter: ['q' => '', 'kecamatan' => (string) $kecamatan->id, 'jenis' => 'masjid', 'status' => ''])
            ->assertSee('1 Kecamatan, 1 Desa/Kelurahan')
            ->call('resetFilter')
            ->assertSet('kecamatan', '')
            ->assertSet('jenis', '')
            ->assertSee('2 Kecamatan, 3 Desa/Kelurahan');
    }

    public function test_filter_dibaca_dari_query_string(): void
    {
        TanahWakaf::factory()->create(['status_sertifikat' => StatusSertifikat::Proses]);
        TanahWakaf::factory()->count(2)->create();

        Livewire::withQueryParams(['status' => 'proses'])
            ->test('pages::publik.peta-wakaf')
            ->assertSet('status', 'proses')
            ->assertSee('1 Kecamatan, 1 Desa/Kelurahan');
    }

    public function test_detail_tanah_terpublikasi_tampil_tanpa_nik(): void
    {
        $tanah = TanahWakaf::factory()->bersertifikat()->create([
            'nama' => 'Masjid Baitul Makmur',
            'wakif_nik' => '1504999988887777',
            'nomor_aiw' => 'W.2/123/2020',
        ]);

        $this->get(route('peta.show', $tanah->kode))
            ->assertOk()
            ->assertSee('Masjid Baitul Makmur')
            ->assertSee($tanah->kode)
            ->assertSee('W.2/123/2020')
            ->assertSee('Sudah Bersertifikat')
            ->assertSee('Kembali ke Peta Wakaf')
            ->assertSee('x-data="petaWakaf(', false)
            ->assertDontSee('1504999988887777');
    }

    public function test_detail_tanah_belum_terpublikasi_404(): void
    {
        $tanah = TanahWakaf::factory()->tanpaAiw()->create();

        $this->get(route('peta.show', $tanah->kode))->assertNotFound();
    }
}
