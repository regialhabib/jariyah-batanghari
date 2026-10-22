<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPengajuan;
use App\Enums\StatusPengajuan;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    private Desa $desa;

    private User $kua;

    private User $bpn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $kecamatan = Kecamatan::factory()->create();
        $this->desa = Desa::factory()->for($kecamatan)->create();
        $this->kua = User::factory()->kua($kecamatan)->create();
        $this->bpn = User::factory()->bpn()->create();
    }

    private function pengajuan(JenisPengajuan $jenis, StatusPengajuan $status, ?Desa $desa = null, string $nama = 'Masjid Al-Ikhlas'): Pengajuan
    {
        $nazir = User::factory()->nazir()->create();
        $tanah = TanahWakaf::factory()->milik($nazir)->tanpaAiw()->create(['desa_id' => ($desa ?? $this->desa)->id, 'nama' => $nama]);

        return Pengajuan::factory()->diajukan()->jenis($jenis)->create([
            'nazir_id' => $nazir->id, 'tanah_wakaf_id' => $tanah->id, 'status' => $status,
        ]);
    }

    public function test_dashboard_kua_menampilkan_angka_sesuai_kecamatannya(): void
    {
        $this->pengajuan(JenisPengajuan::Aiw, StatusPengajuan::Diajukan);
        $this->pengajuan(JenisPengajuan::Aiw, StatusPengajuan::Diajukan, Desa::factory()->create(), 'Masjid Kecamatan Lain');
        TanahWakaf::factory()->count(2)->bersertifikat()->create(['desa_id' => $this->desa->id]);
        TanahWakaf::factory()->count(3)->create();

        $this->actingAs($this->kua)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Selamat Datang, '.$this->kua->name)
            ->assertSee('Sebaran Tanah Wakaf per Kecamatan')
            ->assertSee('Lihat Peta Lengkap')
            ->assertSee('Masjid Al-Ikhlas')
            ->assertDontSee('Masjid Kecamatan Lain');

        $this->actingAs($this->kua);
        $dashboard = Livewire::test('pages::admin.dashboard')->instance();
        $this->assertSame(3, $dashboard->ringkasan['total']);
        $this->assertSame(2, $dashboard->ringkasan['sudah']);
        $this->assertSame(1, $dashboard->ringkasan['belum']);
        $this->assertSame(1, $dashboard->pengajuanBaru);
        $this->assertCount(1, $dashboard->perluDiperiksa);
        $this->assertCount(1, $dashboard->sebaran);
        $this->assertCount(3, $dashboard->dataPeta);
        $this->assertArrayNotHasKey('wakif_nik', $dashboard->dataPeta[0]);
        $this->assertNull($dashboard->dataPeta[0]['url']);
    }

    public function test_dashboard_bpn_melihat_semua_dan_hanya_antrian_sertifikat(): void
    {
        $this->pengajuan(JenisPengajuan::Aiw, StatusPengajuan::Diajukan, nama: 'Pengajuan AIW');
        $this->pengajuan(JenisPengajuan::SertifikatBelum, StatusPengajuan::Diajukan, nama: 'Sertifikat Baru');
        $this->pengajuan(JenisPengajuan::SertifikatBersertifikat, StatusPengajuan::Terverifikasi, nama: 'Menunggu Terbit');
        TanahWakaf::factory()->count(2)->create();

        $this->actingAs($this->bpn);
        $komponen = Livewire::test('pages::admin.dashboard')
            ->assertSee('Sertifikat Baru')
            ->assertSee('Menunggu Terbit');
        $dashboard = $komponen->instance();

        $this->assertSame(5, $dashboard->ringkasan['total']);
        $this->assertSame(2, $dashboard->pengajuanBaru);
        // Pengajuan AIW bukan wewenang BPN.
        $this->assertSame(['Menunggu Terbit', 'Sertifikat Baru'], $dashboard->perluDiperiksa->map(fn ($p) => $p->tanahWakaf->nama)->sort()->values()->all());
        $this->assertStringContainsString('/admin/tanah/', $dashboard->dataPeta[0]['url']);
    }
}
