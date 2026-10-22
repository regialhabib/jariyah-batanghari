<?php

namespace Tests\Feature\Publik;

use App\Enums\StatusSertifikat;
use App\Models\Artikel;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use App\Models\Unduhan;
use App\Models\User;
use Database\Seeders\KontenSeeder;
use Database\Seeders\PersyaratanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman publik Fase 2 (docs/01-rancangan-halaman.md): render untuk tamu, konten kunci,
 * statistik Beranda, dan privasi Daftar Nazir.
 */
class HalamanPublikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, KontenSeeder::class, PersyaratanSeeder::class]);
    }

    public function test_beranda_menampilkan_hero_dan_kartu_statistik(): void
    {
        $this->get(route('beranda'))
            ->assertOk()
            ->assertSee('JARIYAH BATANG HARI')
            ->assertSee('Sistem Informasi Pengelolaan dan Monitoring Tanah Wakaf Kabupaten Batang Hari')
            ->assertSee('Wakaf untuk Masa Depan Umat')
            ->assertSee('Daftar Sekarang')
            ->assertSee(route('register'))
            ->assertSeeInOrder(['Total Tanah Wakaf', 'Bersertifikat', 'Belum Bersertifikat', 'Kecamatan Terdaftar'])
            ->assertSee('Persyaratan Pengajuan Tanah Wakaf')
            ->assertSee('Lihat Semua Persyaratan');
    }

    public function test_statistik_beranda_hanya_menghitung_tanah_yang_dipublikasikan(): void
    {
        $desaA = Desa::factory()->create();
        $desaB = Desa::factory()->create();
        $desaC = Desa::factory()->create();

        TanahWakaf::factory()->count(4)->bersertifikat()->create(['desa_id' => $desaA->id]);
        TanahWakaf::factory()->count(2)->create(['desa_id' => $desaB->id, 'status_sertifikat' => StatusSertifikat::Belum]);
        TanahWakaf::factory()->create(['desa_id' => $desaB->id, 'status_sertifikat' => StatusSertifikat::Proses]);
        // Tidak dipublikasikan: tidak boleh ikut dihitung.
        TanahWakaf::factory()->count(5)->bersertifikat()->create(['desa_id' => $desaC->id, 'is_published' => false]);

        $statistik = collect(Livewire::test('pages::publik.beranda')->instance()->statistik)->pluck('value', 'label');

        $this->assertSame([
            'Total Tanah Wakaf' => 7,
            'Bersertifikat' => 4,
            'Belum Bersertifikat' => 3,
            'Kecamatan Terdaftar' => 2,
        ], $statistik->all());
    }

    public function test_tentang_menampilkan_konten_dari_pengaturan(): void
    {
        $this->get(route('tentang'))
            ->assertOk()
            ->assertSee('Apa itu Jariyah Batang Hari?')
            ->assertSee(setting('tentang_tujuan'))
            ->assertSee('Mempercepat proses verifikasi')
            ->assertSee('Kemenag / KUA')
            ->assertSee('Wakaf Lebih Mudah, Aman dan Terdata.');
    }

    public function test_informasi_wakaf_hanya_menampilkan_artikel_terbit(): void
    {
        Artikel::factory()->create(['judul' => 'Artikel Draf Rahasia', 'is_published' => false]);

        $this->get(route('informasi.index'))
            ->assertOk()
            ->assertSee('Apa Itu Wakaf?')
            ->assertSee('Kepastian Hukum Wakaf')
            ->assertSee(route('informasi.show', 'apa-itu-wakaf'))
            ->assertSee('menanam kebaikan yang terus tumbuh', false)
            ->assertSee('Lihat Panduan Wakaf')
            ->assertDontSee('Artikel Draf Rahasia');
    }

    public function test_detail_artikel_dan_artikel_lain(): void
    {
        $this->get(route('informasi.show', 'nazir'))
            ->assertOk()
            ->assertSee('Nazir - Jariyah Batang Hari')
            ->assertSee('Melaporkan pelaksanaan tugas kepada Badan Wakaf Indonesia.')
            ->assertSee('Artikel Lainnya')
            ->assertSee('Akta Ikrar Wakaf (AIW)');
    }

    public function test_artikel_belum_terbit_404(): void
    {
        $artikel = Artikel::factory()->create(['is_published' => false]);

        $this->get(route('informasi.show', $artikel->slug))->assertNotFound();
        $this->get(route('informasi.show', 'tidak-ada'))->assertNotFound();
    }

    public function test_persyaratan_tiga_kolom_dari_database(): void
    {
        $this->get(route('persyaratan'))
            ->assertOk()
            ->assertSee('Daftar Persyaratan Pengajuan Tanah Wakaf')
            ->assertSeeInOrder([
                '1. Syarat Pembuatan AIW di KUA Kecamatan',
                'Fotokopi KTP saksi',
                '2. Syarat Sertifikat Wakaf (Tanah Sudah Bersertifikat)',
                'Sertifikat asli',
                '3. Syarat Sertifikat Wakaf (Tanah Belum Bersertifikat)',
                'Bukti kepemilikan tanah/alas hak',
            ])
            ->assertSee('(jika ada)')
            ->assertSee('Lihat Persyaratan Lengkap')
            ->assertSee('Unduh Formulir')
            ->assertSee('Daftar Akun');
    }

    public function test_persyaratan_unduh_formulir_mengarah_ke_file_jika_tersedia(): void
    {
        $formulir = Unduhan::query()->where('judul', 'Formulir Permohonan')->firstOrFail();

        $this->get(route('persyaratan'))->assertDontSee($formulir->url());

        $formulir->update(['file' => 'unduhan/formulir.pdf']);

        $this->get(route('persyaratan'))->assertSee($formulir->url());
    }

    public function test_panduan_langkah_dan_dokumen_penting(): void
    {
        $tersedia = Unduhan::query()->where('judul', 'Formulir Permohonan')->firstOrFail();
        $tersedia->update(['file' => 'unduhan/formulir.pdf']);
        $belum = Unduhan::query()->where('judul', 'Contoh Surat Kuasa')->firstOrFail();

        $this->get(route('panduan'))
            ->assertOk()
            ->assertSee('Panduan Pengurusan Tanah Wakaf')
            ->assertSeeInOrder(['Persiapan Dokumen', 'Pendaftaran Akun', 'Pengajuan Data Wakaf', 'Verifikasi dan Validasi', 'Penerbitan Sertifikat'])
            ->assertSee('Dokumen Penting')
            ->assertSee($tersedia->url())
            ->assertDontSee($belum->url())
            ->assertSee('Segera tersedia')
            ->assertSee(route('unduhan.semua'))
            ->assertSee('Unduh Semua Dokumen');
    }

    public function test_faq_menampilkan_pertanyaan_terbit(): void
    {
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Apakah layanan ini dipungut biaya?')
            ->assertSee('Format file apa yang bisa diunggah?')
            ->assertSee('Hubungi Kami');
    }

    public function test_kontak_menampilkan_info_form_dan_kontak_nazir(): void
    {
        $this->get(route('kontak'))
            ->assertOk()
            ->assertSee(setting('alamat'))
            ->assertSee('(0743) 21045')
            ->assertSee('kab-batanghari@atrbpn.go.id')
            ->assertSee('Senin – Jumat (08.00 – 16.00 WIB)')
            ->assertSee('Kirim Pesan')
            ->assertSee('Kontak Nazir')
            ->assertSee(route('nazir.publik'));
    }

    public function test_daftar_nazir_tidak_membocorkan_data_pribadi(): void
    {
        $kecamatan = Kecamatan::factory()->create(['nama' => 'Muara Bulian']);
        $desa = Desa::factory()->create(['kecamatan_id' => $kecamatan->id]);

        $nazir = User::factory()->nazir()->create([
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad.rahasia@example.com',
            'phone' => '081234567890',
            'nik' => '1504011234567890',
        ]);
        TanahWakaf::factory()->count(2)->milik($nazir)->create(['desa_id' => $desa->id]);
        TanahWakaf::factory()->create(['desa_id' => $desa->id, 'nama_nazir' => 'Haji Salim', 'wakif_nik' => '1504999988887777']);

        $this->get(route('nazir.publik'))
            ->assertOk()
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Haji Salim')
            ->assertSee('Muara Bulian')
            ->assertSee('2 bidang tanah wakaf')
            ->assertDontSee('ahmad.rahasia@example.com')
            ->assertDontSee('081234567890')
            ->assertDontSee('1504011234567890')
            ->assertDontSee('1504999988887777');
    }

    public function test_daftar_nazir_hanya_nazir_aktif_dengan_tanah_terbit(): void
    {
        $desa = Desa::factory()->create();

        $tanpaTanah = User::factory()->nazir()->create(['name' => 'Nazir Tanpa Tanah']);
        $nonaktif = User::factory()->nazir()->nonaktif()->create(['name' => 'Nazir Nonaktif']);
        TanahWakaf::factory()->milik($nonaktif)->create(['desa_id' => $desa->id]);
        $draf = User::factory()->nazir()->create(['name' => 'Nazir Tanah Draf']);
        TanahWakaf::factory()->milik($draf)->tanpaAiw()->create(['desa_id' => $desa->id]);

        $this->get(route('nazir.publik'))
            ->assertOk()
            ->assertDontSee('Nazir Tanpa Tanah')
            ->assertDontSee('Nazir Nonaktif')
            ->assertDontSee('Nazir Tanah Draf');
    }

    public function test_daftar_nazir_bisa_difilter_per_kecamatan_dan_nama(): void
    {
        $desaA = Desa::factory()->create(['kecamatan_id' => Kecamatan::factory()->create(['nama' => 'Kecamatan Satu'])->id]);
        $desaB = Desa::factory()->create(['kecamatan_id' => Kecamatan::factory()->create(['nama' => 'Kecamatan Dua'])->id]);

        TanahWakaf::factory()->create(['desa_id' => $desaA->id, 'nama_nazir' => 'Nazir Pertama']);
        TanahWakaf::factory()->create(['desa_id' => $desaB->id, 'nama_nazir' => 'Nazir Kedua']);

        Livewire::test('pages::publik.daftar-nazir')
            ->assertSee('Nazir Pertama')
            ->assertSee('Nazir Kedua')
            ->set('kecamatan', (string) $desaA->kecamatan_id)
            ->assertSee('Nazir Pertama')
            ->assertDontSee('Nazir Kedua')
            ->set('kecamatan', '')
            ->set('cari', 'kedua')
            ->assertSee('Nazir Kedua')
            ->assertDontSee('Nazir Pertama');
    }
}
