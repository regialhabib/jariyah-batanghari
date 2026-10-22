<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TanahAdminTest extends TestCase
{
    use RefreshDatabase;

    private Kecamatan $kecamatan;

    private Desa $desa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kecamatan = Kecamatan::factory()->create(['nama' => 'Muara Bulian']);
        $this->desa = Desa::factory()->for($this->kecamatan)->create(['nama' => 'Rengas Condong']);
    }

    public function test_kua_hanya_melihat_tanah_di_kecamatannya(): void
    {
        $kua = User::factory()->kua($this->kecamatan)->create();
        TanahWakaf::factory()->create(['desa_id' => $this->desa->id, 'nama' => 'Masjid Sendiri']);
        TanahWakaf::factory()->create(['nama' => 'Masjid Lain Kecamatan']);

        $this->actingAs($kua)->get(route('admin.tanah.index'))
            ->assertOk()
            ->assertSee('Masjid Sendiri')
            ->assertDontSee('Masjid Lain Kecamatan')
            ->assertDontSee('Tambah Data');
    }

    public function test_bpn_melihat_semua_dan_bisa_mencari(): void
    {
        $bpn = User::factory()->bpn()->create();
        TanahWakaf::factory()->create(['desa_id' => $this->desa->id, 'nama' => 'Masjid Satu']);
        TanahWakaf::factory()->create(['nama' => 'Makam Dua']);

        $this->actingAs($bpn);
        Livewire::test('pages::admin.tanah.index')
            ->assertSee('Masjid Satu')
            ->assertSee('Makam Dua')
            ->assertSee('Tambah Data')
            ->set('q', 'Makam')
            ->assertDontSee('Masjid Satu')
            ->assertSee('Makam Dua');
    }

    public function test_kua_tidak_bisa_menghapus_lewat_aksi_livewire(): void
    {
        $kua = User::factory()->kua($this->kecamatan)->create();
        $tanah = TanahWakaf::factory()->create(['desa_id' => $this->desa->id]);

        $this->actingAs($kua);
        Livewire::test('pages::admin.tanah.index')
            ->call('konfirmasiHapus', $tanah->id)
            ->assertForbidden();

        $this->assertModelExists($tanah);
    }

    public function test_kua_melihat_detail_dengan_nik_tersamar(): void
    {
        $kua = User::factory()->kua($this->kecamatan)->create();
        $sendiri = TanahWakaf::factory()->create(['desa_id' => $this->desa->id, 'wakif_nik' => '1504010101017788']);

        $this->actingAs($kua);
        Livewire::test('pages::admin.tanah.index')
            ->call('lihat', $sendiri->id)
            ->assertSee('7788')
            ->assertDontSee('1504010101017788');
    }

    public function test_kua_tidak_bisa_melihat_detail_kecamatan_lain(): void
    {
        $kua = User::factory()->kua($this->kecamatan)->create();
        $lain = TanahWakaf::factory()->create();

        $this->actingAs($kua);
        Livewire::test('pages::admin.tanah.index')
            ->call('lihat', $lain->id)
            ->assertNotFound()
            ->assertSet('detailId', null);
    }

    public function test_bpn_menghapus_tanah_tanpa_pengajuan(): void
    {
        $bpn = User::factory()->bpn()->create();
        $tanah = TanahWakaf::factory()->create(['desa_id' => $this->desa->id]);

        $this->actingAs($bpn);
        Livewire::test('pages::admin.tanah.index')
            ->call('konfirmasiHapus', $tanah->id)
            ->call('hapus')
            ->assertHasNoErrors();

        $this->assertModelMissing($tanah);
    }

    public function test_bpn_menambah_tanah_lewat_form(): void
    {
        Storage::fake('public');
        $bpn = User::factory()->bpn()->create();
        $nazir = User::factory()->nazir()->create();

        $this->actingAs($bpn);
        Livewire::test('pages::admin.tanah.form')
            ->set('nama', 'Masjid Baru')
            ->set('jenis_penggunaan', JenisPenggunaan::Masjid->value)
            ->set('sumber_nazir', 'akun')
            ->set('nazir_id', (string) $nazir->id)
            ->set('wakif_nama', 'H. Abdullah')
            ->set('wakif_nik', '1504010101010001')
            ->set('kecamatan_id', (string) $this->kecamatan->id)
            ->set('desa_id', (string) $this->desa->id)
            ->set('luas_m2', '1500')
            ->set('status_sertifikat', StatusSertifikat::Belum->value)
            ->set('nomor_aiw', 'W.2/1/2020')
            ->set('tanggal_aiw', '2020-01-02')
            ->set('latitude', -1.7)
            ->set('longitude', 103.27)
            ->set('batas_geojson', ['type' => 'Polygon', 'coordinates' => [[[103.27, -1.7], [103.28, -1.7], [103.28, -1.71], [103.27, -1.7]]]])
            ->set('foto', UploadedFile::fake()->image('foto.jpg'))
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.tanah.index'));

        $tanah = TanahWakaf::query()->where('nama', 'Masjid Baru')->firstOrFail();
        $this->assertSame('manual', $tanah->sumber);
        $this->assertSame($nazir->id, $tanah->nazir_id);
        $this->assertNull($tanah->nama_nazir);
        $this->assertSame('2020-01-02', $tanah->tanggal_aiw->format('Y-m-d'));
        $this->assertSame('Polygon', $tanah->batas_geojson['type']);
        $this->assertStringStartsWith('tanah/', (string) $tanah->foto);
        Storage::disk('public')->assertExists($tanah->foto);
    }

    public function test_form_validasi_dalam_bahasa_indonesia(): void
    {
        $bpn = User::factory()->bpn()->create();
        $desaLain = Desa::factory()->create();

        $this->actingAs($bpn);
        Livewire::test('pages::admin.tanah.form')
            ->set('kecamatan_id', (string) $this->kecamatan->id)
            ->set('desa_id', (string) $desaLain->id)
            ->set('status_sertifikat', 'sudah')
            ->set('wakif_nik', '123')
            ->call('simpan')
            ->assertHasErrors(['nama' => 'required', 'desa_id', 'nomor_sertifikat', 'wakif_nik', 'latitude'])
            ->assertSee('Tentukan titik lokasi pada peta.');

        $this->assertSame(0, TanahWakaf::count());
    }

    public function test_bpn_mengubah_tanah_dan_mengganti_foto(): void
    {
        Storage::fake('public');
        $bpn = User::factory()->bpn()->create();
        Storage::disk('public')->put('tanah/lama.jpg', 'x');
        $tanah = TanahWakaf::factory()->create(['desa_id' => $this->desa->id, 'foto' => 'tanah/lama.jpg', 'sumber' => 'impor']);

        $this->actingAs($bpn);
        Livewire::test('pages::admin.tanah.form', ['tanah' => $tanah])
            ->assertSet('nama', $tanah->nama)
            ->assertSet('kecamatan_id', (string) $this->kecamatan->id)
            ->set('nama', 'Nama Diubah')
            ->set('sumber_nazir', 'teks')
            ->set('nama_nazir', 'Pak Nazir')
            ->set('foto', UploadedFile::fake()->image('baru.png'))
            ->call('simpan')
            ->assertHasNoErrors();

        $tanah->refresh();
        $this->assertSame('Nama Diubah', $tanah->nama);
        $this->assertSame('Pak Nazir', $tanah->nama_nazir);
        $this->assertSame('impor', $tanah->sumber);
        Storage::disk('public')->assertMissing('tanah/lama.jpg');
        Storage::disk('public')->assertExists($tanah->foto);
    }

    public function test_peta_admin_memuat_tanah_belum_terbit_dalam_cakupan(): void
    {
        $kua = User::factory()->kua($this->kecamatan)->create();
        $bpn = User::factory()->bpn()->create();
        TanahWakaf::factory()->tanpaAiw()->create(['desa_id' => $this->desa->id, 'nama' => 'Belum Terbit']);
        TanahWakaf::factory()->create(['nama' => 'Kecamatan Lain']);
        TanahWakaf::factory()->bersertifikat()->create(['nama' => 'Sudah Sertifikat']);

        $this->actingAs($kua)->get(route('admin.peta'))->assertOk();

        $data = Livewire::test('pages::admin.peta')->instance()->dataPeta;
        $this->assertCount(1, $data);
        $this->assertSame('Belum Terbit', $data[0]['nama']);
        $this->assertNull($data[0]['url']);
        $this->assertArrayNotHasKey('wakif_nik', $data[0]);

        $this->actingAs($bpn);
        $peta = Livewire::test('pages::admin.peta')->set('status', 'belum');
        $data = $peta->instance()->dataPeta;
        $this->assertCount(2, $data);
        $this->assertSame(2, $peta->instance()->jumlah['total']);
        $this->assertSame(1, $peta->instance()->jumlah['belum_terbit']);
        $this->assertStringContainsString('/admin/tanah/', $data[0]['url']);
    }

    public function test_kua_dilarang_membuka_form(): void
    {
        $kua = User::factory()->kua($this->kecamatan)->create();
        $tanah = TanahWakaf::factory()->create(['desa_id' => $this->desa->id]);

        $this->actingAs($kua)->get(route('admin.tanah.create'))->assertForbidden();
        $this->actingAs($kua)->get(route('admin.tanah.edit', $tanah))->assertForbidden();

        Livewire::test('pages::admin.tanah.form')->assertForbidden();
    }
}
