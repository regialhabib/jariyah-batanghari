<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPengajuan;
use App\Enums\JenisPenggunaan;
use App\Enums\KategoriDokumen;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Enums\StatusValidasiDokumen;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use App\Services\PengajuanWorkflow;
use Database\Seeders\PersyaratanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PengajuanAdminTest extends TestCase
{
    use RefreshDatabase;

    private PengajuanWorkflow $workflow;

    private Kecamatan $kecamatan;

    private Desa $desa;

    private User $nazir;

    private User $kua;

    private User $bpn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PersyaratanSeeder::class]);
        Storage::fake('local');
        Notification::fake();

        $this->workflow = app(PengajuanWorkflow::class);
        $this->kecamatan = Kecamatan::factory()->create();
        $this->desa = Desa::factory()->for($this->kecamatan)->create();
        $this->nazir = User::factory()->nazir()->create(['name' => 'Nazir Teladan']);
        $this->kua = User::factory()->kua($this->kecamatan)->create();
        $this->bpn = User::factory()->bpn()->create();
    }

    private function aiwTerkirim(?Desa $desa = null, string $nama = 'Masjid Al-Ikhlas'): Pengajuan
    {
        $pengajuan = $this->workflow->buatDraf($this->nazir, JenisPengajuan::Aiw, dataTanah: [
            'nama' => $nama,
            'wakif_nama' => 'H. Abdullah',
            'wakif_nik' => '1504010101019876',
            'desa_id' => ($desa ?? $this->desa)->id,
            'luas_m2' => 1500,
            'jenis_penggunaan' => JenisPenggunaan::Masjid,
            'latitude' => -1.7,
            'longitude' => 103.27,
        ]);
        $this->unggahSemua($pengajuan);
        $this->workflow->kirim($pengajuan, $this->nazir);

        return $pengajuan->refresh();
    }

    private function sertifikatTerkirim(): Pengajuan
    {
        $tanah = TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id, 'nama' => 'Tanah Sertifikat']);
        $pengajuan = $this->workflow->buatDraf($this->nazir, JenisPengajuan::SertifikatBelum, $tanah);
        $this->unggahSemua($pengajuan);
        $this->workflow->kirim($pengajuan, $this->nazir);

        return $pengajuan->refresh();
    }

    private function unggahSemua(Pengajuan $pengajuan): void
    {
        foreach ($pengajuan->persyaratans()->where('wajib', true) as $syarat) {
            $this->workflow->unggahDokumen($pengajuan, $syarat, UploadedFile::fake()->create('dok.pdf', 20, 'application/pdf'), $this->nazir);
        }
    }

    private function validasiSemua(Pengajuan $pengajuan, User $pemeriksa): void
    {
        foreach ($pengajuan->dokumens()->get() as $dokumen) {
            $this->workflow->validasiDokumen($dokumen, $pemeriksa, StatusValidasiDokumen::Valid);
        }
    }

    public function test_kua_hanya_melihat_pengajuan_kecamatannya_tanpa_draf(): void
    {
        $this->aiwTerkirim(nama: 'Masjid Kecamatan Sendiri');
        $this->aiwTerkirim(Desa::factory()->create(), 'Masjid Kecamatan Lain');
        $this->workflow->buatDraf($this->nazir, JenisPengajuan::Aiw, dataTanah: [
            'nama' => 'Masjid Masih Draf', 'wakif_nama' => 'X', 'desa_id' => $this->desa->id, 'luas_m2' => 10,
            'jenis_penggunaan' => JenisPenggunaan::Masjid, 'latitude' => -1.7, 'longitude' => 103.2,
        ]);

        $this->actingAs($this->kua)->get(route('admin.pengajuan.index'))
            ->assertOk()
            ->assertSee('Masjid Kecamatan Sendiri')
            ->assertDontSee('Masjid Kecamatan Lain')
            ->assertDontSee('Masjid Masih Draf');
    }

    public function test_filter_status_dari_url_dan_tab_perlu_diperiksa(): void
    {
        $aiw = $this->aiwTerkirim(nama: 'Masjid AIW');
        $this->sertifikatTerkirim();
        $this->workflow->mintaPerbaikan($aiw, $this->kua, 'Perbaiki');

        $this->actingAs($this->bpn);
        Livewire::withQueryParams(['status' => 'diajukan'])->test('pages::admin.pengajuan.index')
            ->assertSet('status', 'diajukan')
            ->assertSee('Tanah Sertifikat')
            ->assertDontSee('Masjid AIW');

        Livewire::test('pages::admin.pengajuan.index')
            ->call('pilihTab', 'periksa')
            ->assertSee('Tanah Sertifikat')
            ->assertDontSee('Masjid AIW')
            ->call('pilihTab', '')
            ->assertSee('Masjid AIW')
            ->set('q', 'Teladan')
            ->assertSee('Masjid AIW');
    }

    public function test_kua_memvalidasi_dokumen_dan_mengesahkan_aiw_dari_halaman(): void
    {
        $pengajuan = $this->aiwTerkirim();
        $this->actingAs($this->kua);

        $komponen = Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $pengajuan])
            ->assertSee('Sahkan AIW')
            ->assertDontSee('Catat Sertifikat Terbit')
            ->assertSee('9876')
            ->assertDontSee('1504010101019876')
            ->assertSee($this->nazir->email);

        // Belum semua valid: workflow menolak dan pesannya tampil.
        $komponen->set('nomorAiw', 'W.2/10/2026')->set('tanggalAiw', now()->format('Y-m-d'))
            ->call('sahkanAiw')
            ->assertHasErrors('dokumen')
            ->assertSee('Semua dokumen persyaratan harus dinyatakan valid');

        foreach ($pengajuan->dokumens()->get() as $dokumen) {
            $komponen->call('tandaiValid', $dokumen->id)->assertHasNoErrors();
        }
        $this->assertFalse($pengajuan->dokumens()->where('status_validasi', '!=', StatusValidasiDokumen::Valid->value)->exists());

        $komponen->set('nomorAiw', 'W.2/10/2026')
            ->set('tanggalAiw', now()->subDay()->format('Y-m-d'))
            ->set('scanAiw', UploadedFile::fake()->create('aiw.pdf', 200, 'application/pdf'))
            ->call('sahkanAiw')
            ->assertHasNoErrors();

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::AiwDisahkan, $pengajuan->status);
        $this->assertSame('W.2/10/2026', $pengajuan->tanahWakaf->nomor_aiw);
        $this->assertTrue($pengajuan->tanahWakaf->is_published);
        $hasil = $pengajuan->dokumens()->where('kategori', KategoriDokumen::HasilAiw->value)->firstOrFail();
        Storage::disk('local')->assertExists($hasil->path);
    }

    public function test_dokumen_tidak_valid_butuh_catatan_lalu_minta_perbaikan(): void
    {
        $pengajuan = $this->aiwTerkirim();
        $dokumen = $pengajuan->dokumens()->firstOrFail();
        $this->actingAs($this->kua);

        Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $pengajuan])
            ->call('bukaTidakValid', $dokumen->id)
            ->call('tandaiTidakValid')
            ->assertHasErrors('catatanDokumen')
            ->set('catatanDokumen', 'Scan buram')
            ->call('tandaiTidakValid')
            ->assertHasNoErrors()
            ->call('mintaPerbaikan')
            ->assertHasErrors('catatanPerbaikan')
            ->set('catatanPerbaikan', 'Unggah ulang KTP')
            ->call('mintaPerbaikan')
            ->assertHasNoErrors();

        $this->assertSame(StatusValidasiDokumen::TidakValid, $dokumen->refresh()->status_validasi);
        $this->assertSame('Scan buram', $dokumen->catatan);
        $this->assertSame(StatusPengajuan::PerluPerbaikan, $pengajuan->refresh()->status);
    }

    public function test_kua_tidak_melihat_aksi_bpn_dan_tidak_bisa_memverifikasi_sertifikat(): void
    {
        $pengajuan = $this->sertifikatTerkirim();
        $this->actingAs($this->kua);

        Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $pengajuan])
            ->assertOk()
            ->assertDontSee('Minta Perbaikan')
            ->assertSee('Anda hanya dapat melihat')
            ->call('verifikasi')
            ->assertForbidden();

        $dokumen = $pengajuan->dokumens()->firstOrFail();
        Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $pengajuan])
            ->call('tandaiValid', $dokumen->id)
            ->assertForbidden();

        $this->assertSame(StatusPengajuan::Diajukan, $pengajuan->refresh()->status);
        $this->assertSame(StatusValidasiDokumen::Menunggu, $dokumen->refresh()->status_validasi);
    }

    public function test_bpn_tidak_bisa_mengesahkan_aiw(): void
    {
        $pengajuan = $this->aiwTerkirim();
        $this->actingAs($this->bpn);

        Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $pengajuan])
            ->assertDontSee('Sahkan AIW')
            ->set('nomorAiw', 'X')
            ->call('sahkanAiw')
            ->assertForbidden();
    }

    public function test_kua_lain_tidak_bisa_membuka_pengajuan(): void
    {
        $pengajuan = $this->aiwTerkirim();
        $kuaLain = User::factory()->kua()->create();

        $this->actingAs($kuaLain)->get(route('admin.pengajuan.show', $pengajuan))->assertForbidden();
    }

    public function test_bpn_verifikasi_lalu_catat_sertifikat_terbit(): void
    {
        $pengajuan = $this->sertifikatTerkirim();
        $this->validasiSemua($pengajuan, $this->bpn);
        $this->actingAs($this->bpn);

        $komponen = Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $pengajuan])
            ->assertSee('Verifikasi')
            ->call('verifikasi')
            ->assertHasNoErrors()
            ->assertSee('Catat Sertifikat Terbit');

        $this->assertSame(StatusPengajuan::Terverifikasi, $pengajuan->refresh()->status);

        $komponen->call('terbitkanSertifikat')
            ->assertHasErrors(['nomorSertifikat'])
            ->set('nomorSertifikat', '10.04.01.02.1.00099')
            ->set('tanggalSertifikat', now()->format('Y-m-d'))
            ->set('scanSertifikat', UploadedFile::fake()->create('sertifikat.pdf', 100, 'application/pdf'))
            ->call('terbitkanSertifikat')
            ->assertHasNoErrors();

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::SertifikatTerbit, $pengajuan->status);
        $this->assertSame(StatusSertifikat::Sudah, $pengajuan->tanahWakaf->status_sertifikat);
        $this->assertSame('10.04.01.02.1.00099', $pengajuan->tanahWakaf->nomor_sertifikat);
        $this->assertTrue($pengajuan->dokumens()->where('kategori', KategoriDokumen::HasilSertifikat->value)->exists());
    }

    public function test_bpn_menolak_pengajuan_dengan_alasan(): void
    {
        $pengajuan = $this->sertifikatTerkirim();
        $this->actingAs($this->bpn);

        Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $pengajuan])
            ->set('alasanTolak', 'Tanah dalam sengketa')
            ->call('tolak')
            ->assertHasNoErrors()
            ->assertSee('Tanah dalam sengketa');

        $this->assertSame(StatusPengajuan::Ditolak, $pengajuan->refresh()->status);
        $this->assertSame(StatusSertifikat::Belum, $pengajuan->tanahWakaf->status_sertifikat);
    }
}
