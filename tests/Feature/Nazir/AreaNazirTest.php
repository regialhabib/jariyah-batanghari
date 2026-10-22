<?php

namespace Tests\Feature\Nazir;

use App\Enums\JenisPengajuan;
use App\Enums\JenisPenggunaan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Enums\StatusValidasiDokumen;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\PengajuanDokumen;
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

class AreaNazirTest extends TestCase
{
    use RefreshDatabase;

    private PengajuanWorkflow $workflow;

    private Kecamatan $kecamatan;

    private Desa $desa;

    private User $nazir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PersyaratanSeeder::class]);
        Storage::fake('local');
        Notification::fake();

        $this->workflow = app(PengajuanWorkflow::class);
        $this->kecamatan = Kecamatan::factory()->create();
        $this->desa = Desa::factory()->for($this->kecamatan)->create();
        $this->nazir = User::factory()->nazir()->create();
    }

    public function test_semua_halaman_nazir_bisa_dibuka(): void
    {
        $pengajuan = $this->draftAiw();
        $this->unggahSemua($pengajuan);
        TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id]);

        $this->actingAs($this->nazir);
        foreach (['nazir.dashboard', 'nazir.tanah', 'nazir.pengajuan.index', 'nazir.pengajuan.create', 'nazir.dokumen', 'nazir.peta'] as $rute) {
            $this->get(route($rute))->assertOk();
        }

        $this->get(route('nazir.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee('Masjid Al-Ikhlas')
            ->assertSee('Kirim Pengajuan');
    }

    public function test_dashboard_menampilkan_hitungan(): void
    {
        TanahWakaf::factory()->milik($this->nazir)->count(2)->create(['desa_id' => $this->desa->id]);

        $draf = $this->draftAiw(); // dokumen belum lengkap
        $diajukan = $this->draftAiwTerkirim();
        Pengajuan::query()->whereKey($diajukan->id)->update(['status' => StatusPengajuan::PerluPerbaikan->value]);
        $selesai = $this->draftAiwTerkirim();
        Pengajuan::query()->whereKey($selesai->id)->update(['status' => StatusPengajuan::AiwDisahkan->value]);

        // Pengajuan Nazir lain tidak ikut dihitung.
        Pengajuan::factory()->create();

        $this->actingAs($this->nazir);
        $statistik = Livewire::test('pages::nazir.dashboard')->instance()->statistik;

        $this->assertSame(5, $statistik['tanah']);          // 2 + 3 tanah dari draf AIW
        $this->assertSame(1, $statistik['diproses']);       // perlu perbaikan
        $this->assertSame(1, $statistik['selesai']);        // aiw disahkan
        $this->assertSame(1, $statistik['belum_lengkap']);  // draf tanpa dokumen
        $this->assertSame(3, $statistik['total_pengajuan']);

        $this->get(route('nazir.dashboard'))->assertSee('Selamat Datang, '.$this->nazir->name)->assertSee('perlu perbaikan');
        $this->assertNotNull($draf);
    }

    public function test_wizard_membuat_draf_aiw_dengan_data_tanah(): void
    {
        $this->actingAs($this->nazir);

        $komponen = Livewire::test('pages::nazir.pengajuan.buat')
            ->call('pilihJenis', 'aiw')
            ->call('lanjut')
            ->assertSet('langkah', 2)
            ->call('lanjut')
            ->assertHasErrors(['nama', 'wakif_nama', 'desa_id', 'luas_m2'])
            ->set('nama', 'Masjid Nurul Huda')
            ->set('jenis_penggunaan', JenisPenggunaan::Masjid->value)
            ->set('wakif_nama', 'H. Ahmad')
            ->set('wakif_nik', '1504012345678901')
            ->set('kecamatan_id', (string) $this->kecamatan->id)
            ->set('desa_id', (string) $this->desa->id)
            ->set('alamat', 'Jl. Lintas Timur')
            ->set('luas_m2', '1250')
            ->call('lanjut')
            ->assertHasNoErrors()
            ->assertSet('langkah', 3)
            ->call('lanjut')
            ->assertHasErrors(['latitude'])
            ->set('latitude', -1.7)
            ->set('longitude', 103.27)
            ->set('batas_geojson', ['type' => 'Polygon', 'coordinates' => [[[103.27, -1.7], [103.28, -1.7], [103.28, -1.71], [103.27, -1.7]]]])
            ->call('lanjut')
            ->assertSet('langkah', 4)
            ->call('lanjut') // langkah 4: tombol submit menyimpan draf
            ->assertHasNoErrors();

        $pengajuan = Pengajuan::query()->where('nazir_id', $this->nazir->id)->sole();
        $komponen->assertRedirect(route('nazir.pengajuan.show', $pengajuan));

        $this->assertSame(JenisPengajuan::Aiw, $pengajuan->jenis);
        $this->assertSame(StatusPengajuan::Draf, $pengajuan->status);
        $tanah = $pengajuan->tanahWakaf;
        $this->assertSame('Masjid Nurul Huda', $tanah->nama);
        $this->assertSame('1504012345678901', $tanah->wakif_nik);
        $this->assertSame($this->desa->id, $tanah->desa_id);
        $this->assertSame($this->nazir->id, $tanah->nazir_id);
        $this->assertFalse($tanah->is_published);
        $this->assertSame('Polygon', $tanah->batas_geojson['type']);
    }

    public function test_wizard_validasi_nik_dan_desa_sesuai_kecamatan(): void
    {
        $desaLain = Desa::factory()->create();
        $this->actingAs($this->nazir);

        Livewire::test('pages::nazir.pengajuan.buat')
            ->set('jenis', 'aiw')
            ->set('langkah', 2)
            ->set('nama', 'Makam Wakaf')
            ->set('jenis_penggunaan', JenisPenggunaan::Makam->value)
            ->set('wakif_nama', 'Fulan')
            ->set('wakif_nik', '12345')
            ->set('kecamatan_id', (string) $this->kecamatan->id)
            ->set('desa_id', (string) $desaLain->id)
            ->set('luas_m2', '300')
            ->call('lanjut')
            ->assertHasErrors(['wakif_nik', 'desa_id'])
            ->assertSet('langkah', 2);
    }

    public function test_wizard_sertifikat_menolak_tanah_tidak_layak(): void
    {
        $tanpaAiw = TanahWakaf::factory()->milik($this->nazir)->tanpaAiw()->create(['desa_id' => $this->desa->id]);
        $sudah = TanahWakaf::factory()->milik($this->nazir)->bersertifikat()->create(['desa_id' => $this->desa->id]);
        $milikLain = TanahWakaf::factory()->milik(User::factory()->nazir()->create())->create(['desa_id' => $this->desa->id]);
        $layak = TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id]);

        $this->actingAs($this->nazir);

        foreach ([$tanpaAiw, $sudah, $milikLain] as $tanah) {
            Livewire::test('pages::nazir.pengajuan.buat')
                ->set('jenis', JenisPengajuan::SertifikatBelum->value)
                ->set('tanah_wakaf_id', $tanah->id)
                ->call('lanjut')
                ->assertHasErrors(['tanah_wakaf_id'])
                ->assertSet('langkah', 1)
                ->call('simpan')
                ->assertHasErrors(['tanah_wakaf_id']);
        }

        $this->assertSame(0, Pengajuan::query()->count());

        // Pra-pilih dari query string, lalu simpan.
        Livewire::withQueryParams(['jenis' => JenisPengajuan::SertifikatBelum->value, 'tanah' => $layak->id])
            ->test('pages::nazir.pengajuan.buat')
            ->assertSet('jenis', JenisPengajuan::SertifikatBelum->value)
            ->assertSet('tanah_wakaf_id', $layak->id)
            ->call('lanjut')
            ->assertSet('langkah', 4)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pengajuans', ['tanah_wakaf_id' => $layak->id, 'jenis' => 'sertifikat_belum', 'status' => 'draf']);

        // Query string untuk tanah orang lain diabaikan.
        Livewire::withQueryParams(['jenis' => JenisPengajuan::SertifikatBelum->value, 'tanah' => $milikLain->id])
            ->test('pages::nazir.pengajuan.buat')
            ->assertSet('tanah_wakaf_id', null);
    }

    public function test_unggah_dokumen_di_halaman_detail(): void
    {
        $pengajuan = $this->draftAiw();
        $syarat = $pengajuan->persyaratans()->first();

        $this->actingAs($this->nazir);
        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan])
            ->set("berkas.{$syarat->id}", UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'))
            ->assertHasNoErrors();

        $dokumen = $pengajuan->dokumens()->sole();
        $this->assertSame($syarat->id, $dokumen->persyaratan_id);
        $this->assertSame('ktp.pdf', $dokumen->nama_asli);
        Storage::disk('local')->assertExists($dokumen->path);
    }

    public function test_unggah_menolak_format_dan_ukuran_tidak_sah(): void
    {
        $pengajuan = $this->draftAiw();
        $syarat = $pengajuan->persyaratans()->first();

        $this->actingAs($this->nazir);
        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan])
            ->set("berkas.{$syarat->id}", UploadedFile::fake()->create('skrip.exe', 10, 'application/x-msdownload'))
            ->assertHasErrors(["berkas.{$syarat->id}"])
            ->set("berkas.{$syarat->id}", UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'))
            ->assertHasErrors(["berkas.{$syarat->id}"]);

        $this->assertSame(0, $pengajuan->dokumens()->count());
    }

    public function test_kirim_ditolak_jika_dokumen_belum_lengkap_dan_berhasil_jika_lengkap(): void
    {
        $pengajuan = $this->draftAiw();

        $this->actingAs($this->nazir);
        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan])
            ->call('kirim')
            ->assertHasErrors(['dokumen']);

        $this->assertSame(StatusPengajuan::Draf, $pengajuan->refresh()->status);

        $this->unggahSemua($pengajuan);

        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan->refresh()])
            ->call('kirim')
            ->assertHasNoErrors();

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::Diajukan, $pengajuan->status);
        $this->assertNotNull($pengajuan->nomor);
        $this->assertSame(1, $pengajuan->riwayats()->count());
    }

    public function test_perlu_perbaikan_hanya_dokumen_tidak_valid_yang_bisa_diganti(): void
    {
        $pengajuan = $this->draftAiwTerkirim();
        [$tidakValid, $valid] = $pengajuan->dokumens()->orderBy('id')->take(2)->get()->all();
        $tidakValid->update(['status_validasi' => StatusValidasiDokumen::TidakValid, 'catatan' => 'Buram']);
        $valid->update(['status_validasi' => StatusValidasiDokumen::Valid]);
        $pengajuan->update(['status' => StatusPengajuan::PerluPerbaikan]);
        Pengajuan::query()->whereKey($pengajuan->id)->update(['catatan_pemeriksa' => 'Perbaiki KTP']);

        $this->actingAs($this->nazir);
        $komponen = Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan->refresh()])
            ->assertSee('Perbaiki KTP')
            ->assertSee('Kirim Ulang');

        // Dokumen valid tidak boleh diganti.
        $komponen->set("berkas.{$valid->persyaratan_id}", UploadedFile::fake()->create('baru.pdf', 50, 'application/pdf'))
            ->assertHasErrors(["berkas.{$valid->persyaratan_id}"]);
        $this->assertDatabaseHas('pengajuan_dokumens', ['id' => $valid->id]);

        // Kirim ulang ditolak selama masih ada dokumen tidak valid.
        $komponen->call('kirim')->assertHasErrors(['dokumen']);

        // Dokumen tidak valid boleh diganti dan kembali menunggu.
        $komponen->set("berkas.{$tidakValid->persyaratan_id}", UploadedFile::fake()->create('ktp-jelas.pdf', 50, 'application/pdf'))
            ->assertHasNoErrors(["berkas.{$tidakValid->persyaratan_id}"]);

        $this->assertDatabaseMissing('pengajuan_dokumens', ['id' => $tidakValid->id]);
        $pengganti = PengajuanDokumen::query()->where('pengajuan_id', $pengajuan->id)->where('persyaratan_id', $tidakValid->persyaratan_id)->sole();
        $this->assertSame(StatusValidasiDokumen::Menunggu, $pengganti->status_validasi);

        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan->refresh()])
            ->call('kirim')
            ->assertHasNoErrors();
        $this->assertSame(StatusPengajuan::Diajukan, $pengajuan->refresh()->status);
    }

    public function test_pengajuan_terkirim_tidak_bisa_diubah(): void
    {
        $pengajuan = $this->draftAiwTerkirim();
        $syarat = $pengajuan->persyaratans()->first();

        $this->actingAs($this->nazir);
        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan])
            ->assertDontSee('Kirim Pengajuan')
            ->set("berkas.{$syarat->id}", UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->assertForbidden();
    }

    public function test_ubah_data_tanah_draf_aiw(): void
    {
        $pengajuan = $this->draftAiw();
        $desaBaru = Desa::factory()->for($this->kecamatan)->create();

        $this->actingAs($this->nazir);
        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan])
            ->call('mulaiUbahTanah')
            ->assertSet('nama', 'Masjid Al-Ikhlas')
            ->set('nama', 'Masjid Al-Ikhlas Baru')
            ->set('desa_id', (string) $desaBaru->id)
            ->set('luas_m2', '2000')
            ->call('simpanTanah')
            ->assertHasNoErrors()
            ->assertSet('mengubahTanah', false);

        $tanah = $pengajuan->tanahWakaf->refresh();
        $this->assertSame('Masjid Al-Ikhlas Baru', $tanah->nama);
        $this->assertSame($desaBaru->id, $tanah->desa_id);
        $this->assertEquals(2000, (float) $tanah->luas_m2);
    }

    public function test_nazir_lain_mendapat_403(): void
    {
        $pengajuan = $this->draftAiw();
        $lain = User::factory()->nazir()->create();

        $this->actingAs($lain)->get(route('nazir.pengajuan.show', $pengajuan))->assertForbidden();

        $this->actingAs($lain);
        Livewire::test('pages::nazir.pengajuan.index')
            ->assertDontSee('Masjid Al-Ikhlas')
            ->call('konfirmasiHapus', $pengajuan->id)
            ->assertNotFound();

        $this->assertDatabaseHas('pengajuans', ['id' => $pengajuan->id]);
    }

    public function test_hapus_hanya_untuk_draf(): void
    {
        $draf = $this->draftAiw();
        $this->unggahSemua($draf);
        $pathDokumen = $draf->dokumens()->first()->path;
        $tanahDraf = $draf->tanah_wakaf_id;
        $terkirim = $this->draftAiwTerkirim();

        $this->actingAs($this->nazir);

        Livewire::test('pages::nazir.pengajuan.index')
            ->call('konfirmasiHapus', $terkirim->id)
            ->assertForbidden();

        Livewire::test('pages::nazir.pengajuan.index')
            ->set('hapusId', $terkirim->id)
            ->call('hapus')
            ->assertForbidden();
        $this->assertDatabaseHas('pengajuans', ['id' => $terkirim->id]);

        Livewire::test('pages::nazir.pengajuan.index')
            ->call('konfirmasiHapus', $draf->id)
            ->assertSet('hapusId', $draf->id)
            ->call('hapus')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('pengajuans', ['id' => $draf->id]);
        $this->assertDatabaseMissing('tanah_wakafs', ['id' => $tanahDraf]);
        Storage::disk('local')->assertMissing($pathDokumen);
    }

    public function test_daftar_pengajuan_filter_dan_pencarian(): void
    {
        $aiw = $this->draftAiw();
        $tanah = TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id, 'nama' => 'Makam Darussalam']);
        $this->workflow->buatDraf($this->nazir, JenisPengajuan::SertifikatBelum, $tanah);

        $this->actingAs($this->nazir);
        Livewire::test('pages::nazir.pengajuan.index')
            ->assertSee('Masjid Al-Ikhlas')
            ->assertSee('Makam Darussalam')
            ->set('jenis', JenisPengajuan::Aiw->value)
            ->assertSee('Masjid Al-Ikhlas')
            ->assertDontSee('Makam Darussalam')
            ->set('jenis', '')
            ->set('q', 'Darussalam')
            ->assertDontSee('Masjid Al-Ikhlas')
            ->assertSee('Makam Darussalam')
            ->set('q', '')
            ->set('status', StatusPengajuan::Diajukan->value)
            ->assertDontSee('Masjid Al-Ikhlas');

        $this->assertNotNull($aiw);
    }

    public function test_data_wakaf_dan_dokumen_hanya_milik_sendiri(): void
    {
        $milik = TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id, 'nama' => 'Mushola Milik Saya']);
        TanahWakaf::factory()->milik(User::factory()->nazir()->create())->create(['nama' => 'Mushola Orang Lain']);
        $draf = $this->draftAiw();
        $this->unggahSemua($draf);

        $lain = Pengajuan::factory()->create();
        $this->workflow->unggahDokumen($lain, $lain->persyaratans()->first(), UploadedFile::fake()->create('rahasia.pdf', 10, 'application/pdf'), $lain->nazir);

        $this->actingAs($this->nazir);

        $this->get(route('nazir.tanah'))
            ->assertSee('Mushola Milik Saya')
            ->assertDontSee('Mushola Orang Lain')
            ->assertSee(route('nazir.pengajuan.create', ['jenis' => 'sertifikat_belum', 'tanah' => $milik->id]));

        $this->get(route('nazir.dokumen'))
            ->assertSee('dok.pdf')
            ->assertDontSee('rahasia.pdf');

        $this->get(route('nazir.peta'))->assertSee('Mushola Milik Saya')->assertDontSee('Mushola Orang Lain');
    }

    public function test_tanah_dengan_pengajuan_sertifikat_aktif_tidak_bisa_diajukan_lagi(): void
    {
        $tanah = TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id, 'status_sertifikat' => StatusSertifikat::Proses]);
        Pengajuan::factory()->jenis(JenisPengajuan::SertifikatBelum)->diajukan()->create(['nazir_id' => $this->nazir->id, 'tanah_wakaf_id' => $tanah->id]);

        $this->actingAs($this->nazir);
        $this->get(route('nazir.tanah'))
            ->assertDontSee(route('nazir.pengajuan.create', ['jenis' => 'sertifikat_belum', 'tanah' => $tanah->id]));

        Livewire::test('pages::nazir.pengajuan.buat')
            ->set('jenis', JenisPengajuan::SertifikatBelum->value)
            ->set('tanah_wakaf_id', $tanah->id)
            ->call('lanjut')
            ->assertHasErrors(['tanah_wakaf_id']);
    }

    private function draftAiw(): Pengajuan
    {
        return $this->workflow->buatDraf($this->nazir, JenisPengajuan::Aiw, dataTanah: [
            'nama' => 'Masjid Al-Ikhlas',
            'wakif_nama' => 'H. Abdullah',
            'desa_id' => $this->desa->id,
            'luas_m2' => 1500,
            'jenis_penggunaan' => JenisPenggunaan::Masjid,
            'latitude' => -1.7,
            'longitude' => 103.27,
        ]);
    }

    private function draftAiwTerkirim(): Pengajuan
    {
        $pengajuan = $this->draftAiw();
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
}
