<?php

namespace Tests\Feature\Akses;

use App\Models\Artikel;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matriks akses rute × role (docs/09-pengujian.md). Memastikan setiap halaman bisa dirender
 * dan role yang tidak berhak mendapat 403.
 */
class RuteAksesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_halaman_publik_bisa_dibuka_tamu(): void
    {
        $artikel = Artikel::factory()->create();
        $tanah = TanahWakaf::factory()->create();

        $urls = [
            route('beranda'), route('tentang'), route('informasi.index'), route('informasi.show', $artikel->slug),
            route('persyaratan'), route('peta'), route('peta.show', $tanah->kode), route('panduan'),
            route('faq'), route('kontak'), route('nazir.publik'), route('login'), route('register'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_tanah_belum_dipublikasikan_tidak_tampil_publik(): void
    {
        $tanah = TanahWakaf::factory()->tanpaAiw()->create();

        $this->get(route('peta.show', $tanah->kode))->assertNotFound();
    }

    public function test_area_nazir(): void
    {
        $nazir = User::factory()->nazir()->create();
        $pengajuan = Pengajuan::factory()->create(['nazir_id' => $nazir->id]);
        $lain = Pengajuan::factory()->create();

        foreach (['nazir.dashboard', 'nazir.tanah', 'nazir.pengajuan.index', 'nazir.pengajuan.create', 'nazir.dokumen', 'nazir.peta'] as $rute) {
            $this->actingAs($nazir)->get(route($rute))->assertOk();
        }

        $this->actingAs($nazir)->get(route('nazir.pengajuan.show', $pengajuan))->assertOk();
        $this->actingAs($nazir)->get(route('nazir.pengajuan.show', $lain))->assertForbidden();
        $this->actingAs($nazir)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_area_admin_per_role(): void
    {
        $admin = User::factory()->admin()->create();
        $bpn = User::factory()->bpn()->create();
        $kua = User::factory()->kua(Kecamatan::factory()->create())->create();
        $tanah = TanahWakaf::factory()->create();

        $semuaPetugas = ['admin.dashboard', 'admin.tanah.index', 'admin.pengajuan.index', 'admin.peta', 'admin.laporan'];
        $bpnSaja = ['admin.tanah.create', 'admin.tanah.impor', 'admin.sertifikat', 'admin.pesan'];
        $adminSaja = ['admin.pengguna.index', 'admin.pengguna.create', 'admin.aktivitas', 'admin.konten.artikel', 'admin.konten.persyaratan',
            'admin.konten.panduan', 'admin.konten.faq', 'admin.konten.unduhan', 'admin.konten.pengaturan'];

        foreach ([...$semuaPetugas, ...$bpnSaja, ...$adminSaja] as $rute) {
            $this->actingAs($admin)->get(route($rute))->assertOk();
        }

        foreach ([...$semuaPetugas, ...$bpnSaja] as $rute) {
            $this->actingAs($bpn)->get(route($rute))->assertOk();
        }
        foreach ($adminSaja as $rute) {
            $this->actingAs($bpn)->get(route($rute))->assertForbidden();
        }

        foreach ($semuaPetugas as $rute) {
            $this->actingAs($kua)->get(route($rute))->assertOk();
        }
        foreach ([...$bpnSaja, ...$adminSaja] as $rute) {
            $this->actingAs($kua)->get(route($rute))->assertForbidden();
        }

        $this->actingAs($bpn)->get(route('admin.tanah.edit', $tanah))->assertOk();
        $this->actingAs($kua)->get(route('admin.tanah.edit', $tanah))->assertForbidden();
    }

    public function test_kua_tidak_bisa_membuka_pengajuan_kecamatan_lain(): void
    {
        $kua = User::factory()->kua(Kecamatan::factory()->create())->create();
        $pengajuan = Pengajuan::factory()->create();

        $this->actingAs($kua)->get(route('admin.pengajuan.show', $pengajuan))->assertForbidden();
    }

    public function test_akun_nonaktif_dikeluarkan(): void
    {
        $user = User::factory()->bpn()->nonaktif()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
