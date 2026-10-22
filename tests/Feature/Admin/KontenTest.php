<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPengajuan;
use App\Models\Artikel;
use App\Models\Faq;
use App\Models\PanduanLangkah;
use App\Models\Pengajuan;
use App\Models\PengajuanDokumen;
use App\Models\Persyaratan;
use App\Models\Unduhan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kelola Konten (Fase 7): artikel, persyaratan, panduan, FAQ, unduhan, pengaturan.
 */
class KontenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_hanya_admin_yang_bisa_membuka_kelola_konten(): void
    {
        $bpn = User::factory()->bpn()->create();
        $rute = ['artikel', 'persyaratan', 'panduan', 'faq', 'unduhan', 'pengaturan'];

        foreach ($rute as $r) {
            $this->actingAs($this->admin)->get(route("admin.konten.$r"))->assertOk()->assertSee('Kelola Konten');
            $this->actingAs($bpn)->get(route("admin.konten.$r"))->assertForbidden();
        }
    }

    public function test_artikel_dibuat_dengan_slug_otomatis_dan_html_disanitasi(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.konten.artikel')
            ->call('tambah')
            ->set('judul', 'Wakaf Produktif di Desa')
            ->assertSet('slug', 'wakaf-produktif-di-desa')
            ->set('ikon', 'heart')
            ->set('ringkasan', 'Ringkasan singkat.')
            ->set('isi', '<div>Isi <strong>tebal</strong></div><script>alert("x")</script>')
            ->call('simpan')
            ->assertHasNoErrors();

        $artikel = Artikel::query()->where('slug', 'wakaf-produktif-di-desa')->firstOrFail();
        $this->assertStringContainsString('<strong>tebal</strong>', $artikel->isi);
        $this->assertStringNotContainsString('<script', $artikel->isi);
        $this->assertStringNotContainsString('alert', $artikel->isi);
        $this->assertSame('heart', $artikel->ikon);
    }

    public function test_slug_artikel_harus_unik_dan_ikon_harus_dari_daftar(): void
    {
        Artikel::factory()->create(['slug' => 'apa-itu-wakaf']);
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.konten.artikel')
            ->call('tambah')
            ->set('judul', 'Apa Itu Wakaf')
            ->set('ikon', 'bukan-ikon')
            ->set('ringkasan', 'R')
            ->set('isi', '<div>Isi</div>')
            ->call('simpan')
            ->assertHasErrors(['slug' => 'unique', 'ikon']);

        $this->assertSame(1, Artikel::query()->where('slug', 'apa-itu-wakaf')->count());
    }

    public function test_artikel_ubah_publish_urutan_dan_hapus(): void
    {
        $artikel = Artikel::factory()->create(['judul' => 'Lama', 'is_published' => true, 'urutan' => 1]);
        $this->actingAs($this->admin);

        $komponen = Livewire::test('pages::admin.konten.artikel')
            ->call('ubah', $artikel->id)
            ->assertSet('judul', 'Lama')
            ->set('judul', 'Baru')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame('Baru', $artikel->fresh()->judul);
        $this->assertSame($artikel->slug, $artikel->fresh()->slug, 'Slug tidak ikut berubah saat mengubah judul artikel lama.');

        $komponen->call('togglePublish', $artikel->id);
        $this->assertFalse($artikel->fresh()->is_published);

        $komponen->call('ubahUrutan', $artikel->id, '7');
        $this->assertSame(7, $artikel->fresh()->urutan);

        $komponen->call('hapus', $artikel->id);
        $this->assertModelMissing($artikel);
    }

    public function test_persyaratan_crud_dan_hapus_diblokir_bila_dipakai(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.konten.persyaratan')
            ->call('tambah', JenisPengajuan::SertifikatBelum->value)
            ->assertSet('jenis_pengajuan', JenisPengajuan::SertifikatBelum->value)
            ->set('nama', 'Surat Keterangan Riwayat Tanah')
            ->call('simpan')
            ->assertHasNoErrors();

        $baru = Persyaratan::query()->where('nama', 'Surat Keterangan Riwayat Tanah')->firstOrFail();
        $this->assertSame(JenisPengajuan::SertifikatBelum, $baru->jenis_pengajuan);
        $this->assertTrue($baru->wajib);

        $dipakai = Persyaratan::factory()->create();
        $pengajuan = Pengajuan::factory()->create();
        PengajuanDokumen::query()->create([
            'pengajuan_id' => $pengajuan->id, 'persyaratan_id' => $dipakai->id, 'kategori' => 'persyaratan',
            'path' => 'pengajuan/x.pdf', 'nama_asli' => 'x.pdf', 'ukuran' => 10,
        ]);

        Livewire::test('pages::admin.konten.persyaratan')
            ->call('hapus', $dipakai->id)
            ->assertSet('terpakaiId', $dipakai->id)
            ->assertSet('jumlahTerpakai', 1)
            ->call('jadikanOpsional');

        $this->assertModelExists($dipakai);
        $this->assertFalse($dipakai->fresh()->wajib);

        Livewire::test('pages::admin.konten.persyaratan')->call('hapus', $baru->id);
        $this->assertModelMissing($baru);
    }

    public function test_panduan_dan_faq_crud(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.konten.panduan')
            ->call('tambah')
            ->set('judul', 'Langkah Baru')
            ->set('deskripsi', 'Deskripsi langkah.')
            ->call('simpan')
            ->assertHasNoErrors();
        $langkah = PanduanLangkah::query()->where('judul', 'Langkah Baru')->firstOrFail();

        Livewire::test('pages::admin.konten.panduan')->call('hapus', $langkah->id);
        $this->assertModelMissing($langkah);

        $faq = Livewire::test('pages::admin.konten.faq')
            ->call('tambah')
            ->set('pertanyaan', 'Berapa lama prosesnya?')
            ->set('jawaban', 'Tergantung kelengkapan berkas.')
            ->set('is_published', false)
            ->call('simpan')
            ->assertHasNoErrors();

        $model = Faq::query()->where('pertanyaan', 'Berapa lama prosesnya?')->firstOrFail();
        $this->assertFalse($model->is_published);

        $faq->call('togglePublish', $model->id);
        $this->assertTrue($model->fresh()->is_published);
    }

    public function test_unduhan_unggah_ganti_dan_hapus_file(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.konten.unduhan')
            ->call('tambah')
            ->set('judul', 'Formulir Permohonan')
            ->set('berkas', UploadedFile::fake()->create('formulir.pdf', 200, 'application/pdf'))
            ->call('simpan')
            ->assertHasNoErrors();

        $unduhan = Unduhan::query()->where('judul', 'Formulir Permohonan')->firstOrFail();
        $this->assertStringStartsWith('unduhan/', $unduhan->file);
        Storage::disk('public')->assertExists($unduhan->file);
        $lama = $unduhan->file;

        // Ganti file: file lama dihapus.
        Livewire::test('pages::admin.konten.unduhan')
            ->call('ubah', $unduhan->id)
            ->set('berkas', UploadedFile::fake()->create('formulir-baru.docx', 100))
            ->call('simpan')
            ->assertHasNoErrors();

        $unduhan->refresh();
        $this->assertNotSame($lama, $unduhan->file);
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($unduhan->file);

        Livewire::test('pages::admin.konten.unduhan')->call('hapusFile', $unduhan->id);
        Storage::disk('public')->assertMissing($unduhan->file);
        $this->assertNull($unduhan->fresh()->file);
    }

    public function test_unduhan_menolak_tipe_dan_ukuran_tidak_sah(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.konten.unduhan')
            ->call('tambah')
            ->set('judul', 'Berbahaya')
            ->set('berkas', UploadedFile::fake()->create('virus.exe', 10))
            ->call('simpan')
            ->assertHasErrors(['berkas' => 'mimes']);

        Livewire::test('pages::admin.konten.unduhan')
            ->call('tambah')
            ->set('judul', 'Terlalu besar')
            ->set('berkas', UploadedFile::fake()->create('besar.pdf', 11 * 1024, 'application/pdf'))
            ->call('simpan')
            ->assertHasErrors(['berkas']);

        $this->assertSame(0, Unduhan::query()->count());
    }

    public function test_pengaturan_tersimpan_dan_terbaca_lewat_helper(): void
    {
        $this->actingAs($this->admin);
        $this->assertNull(setting('telepon'));

        Livewire::test('pages::admin.konten.pengaturan')
            ->set('nilai.telepon', '(0743) 99999')
            ->set('nilai.tentang_manfaat', "Manfaat satu\n\n  Manfaat dua  \n")
            ->set('nilai.sosial_instagram', 'https://instagram.com/bpnbatanghari')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame('(0743) 99999', setting('telepon'));
        $this->assertSame(['Manfaat satu', 'Manfaat dua'], setting_list('tentang_manfaat'));
        $this->assertSame('https://instagram.com/bpnbatanghari', setting('sosial_instagram'));
        $this->assertDatabaseHas('pengaturans', ['kunci' => 'telepon', 'grup' => 'kontak']);

        // Form memuat nilai tersimpan; list ditampilkan satu per baris.
        Livewire::test('pages::admin.konten.pengaturan')
            ->assertSet('nilai.tentang_manfaat', "Manfaat satu\nManfaat dua")
            ->set('nilai.sosial_youtube', 'bukan url')
            ->set('nilai.email', 'bukan-email')
            ->call('simpan')
            ->assertHasErrors(['nilai.sosial_youtube', 'nilai.email']);
    }

    public function test_aksi_livewire_ditolak_untuk_non_admin(): void
    {
        $artikel = Artikel::factory()->create();
        $this->actingAs(User::factory()->bpn()->create());

        Livewire::test('pages::admin.konten.artikel')->assertForbidden();
        $this->assertModelExists($artikel);
    }
}
