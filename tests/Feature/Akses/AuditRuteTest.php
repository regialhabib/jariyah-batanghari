<?php

namespace Tests\Feature\Akses;

use App\Enums\KategoriDokumen;
use App\Enums\StatusValidasiDokumen;
use App\Http\Middleware\PastikanRole;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\PengajuanDokumen;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Audit akses semua rute area (docs/09-pengujian.md, Fase 8). Daftar rute diambil dari router,
 * sehingga rute baru di area admin/nazir otomatis ikut diaudit.
 */
class AuditRuteTest extends TestCase
{
    use RefreshDatabase;

    private Kecamatan $kecamatan;

    private User $nazir;

    private Pengajuan $pengajuan;

    private PengajuanDokumen $dokumen;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->kecamatan = Kecamatan::factory()->create();
        $desa = Desa::factory()->create(['kecamatan_id' => $this->kecamatan->id]);
        $this->nazir = User::factory()->nazir()->create();
        $tanah = TanahWakaf::factory()->tanpaAiw()->create(['nazir_id' => $this->nazir->id, 'desa_id' => $desa->id]);
        $this->pengajuan = Pengajuan::factory()->diajukan()->create(['nazir_id' => $this->nazir->id, 'tanah_wakaf_id' => $tanah->id]);

        Storage::disk('local')->put('pengajuan/contoh.pdf', '%PDF-1.4 contoh');
        $this->dokumen = PengajuanDokumen::create([
            'pengajuan_id' => $this->pengajuan->id,
            'kategori' => KategoriDokumen::Persyaratan,
            'path' => 'pengajuan/contoh.pdf',
            'nama_asli' => 'contoh.pdf',
            'mime' => 'application/pdf',
            'ukuran' => 15,
            'status_validasi' => StatusValidasiDokumen::Menunggu,
        ]);
    }

    public function test_setiap_rute_area_memakai_auth_verified_dan_role(): void
    {
        $this->assertGreaterThan(20, count($this->ruteArea('admin.')));
        $this->assertGreaterThan(5, count($this->ruteArea('nazir.')));

        foreach ($this->ruteArea('admin.') as $route) {
            $this->assertEmpty(array_diff(['auth', 'verified', 'role:admin_bpn|bpn|kua'], $route->gatherMiddleware()), "Rute {$route->getName()} tidak dilindungi.");
        }

        foreach ($this->ruteArea('nazir.') as $route) {
            $this->assertEmpty(array_diff(['auth', 'verified', 'role:nazir'], $route->gatherMiddleware()), "Rute {$route->getName()} tidak dilindungi.");
        }
    }

    public function test_tamu_diarahkan_ke_login_di_semua_rute_terlindungi(): void
    {
        $urls = [
            ...array_map(fn (Route $r) => $this->url($r), [...$this->ruteArea('admin.'), ...$this->ruteArea('nazir.')]),
            route('dashboard'),
            route('dokumen.show', $this->dokumen),
            route('profile.edit'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_nazir_ditolak_di_semua_rute_admin(): void
    {
        foreach ($this->ruteArea('admin.') as $route) {
            $this->actingAs($this->nazir)->get($this->url($route))->assertForbidden();
        }
    }

    #[DataProvider('petugas')]
    public function test_petugas_ditolak_di_semua_rute_nazir(string $state): void
    {
        $petugas = $this->buatPetugas($state);

        foreach ($this->ruteArea('nazir.') as $route) {
            $this->actingAs($petugas)->get($this->url($route))->assertForbidden();
        }
    }

    public function test_nazir_yang_belum_verifikasi_email_diarahkan_ke_halaman_verifikasi(): void
    {
        $nazir = User::factory()->nazir()->unverified()->create();

        $this->actingAs($nazir)->get(route('nazir.dashboard'))->assertRedirect(route('verification.notice'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function petugas(): array
    {
        return ['admin' => ['admin'], 'bpn' => ['bpn'], 'kua' => ['kua']];
    }

    #[DataProvider('petugas')]
    public function test_petugas_boleh_mengekspor_laporan(string $state): void
    {
        $petugas = $this->buatPetugas($state);

        $this->actingAs($petugas)->get(route('admin.laporan.excel'))->assertOk()->assertDownload();
        $this->actingAs($petugas)->get(route('admin.laporan.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_dokumen_privat_hanya_untuk_pemilik_dan_petugas_berwenang(): void
    {
        $url = route('dokumen.show', $this->dokumen);

        $this->actingAs($this->nazir)->get($url)->assertOk();
        $this->actingAs($this->buatPetugas('kua'))->get($url)->assertOk();
        $this->actingAs($this->buatPetugas('bpn'))->get($url)->assertOk();

        $this->actingAs(User::factory()->nazir()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->kua(Kecamatan::factory()->create())->create())->get($url)->assertForbidden();
    }

    /**
     * Aksi Livewire dikirim ke /livewire/update, bukan ke rute halaman. Pembatas role harus didaftarkan
     * sebagai persistent middleware agar ikut dijalankan ulang; perilaku ini tidak bisa dipicu lewat
     * Livewire::test(), jadi yang diperiksa adalah pendaftarannya.
     */
    public function test_pembatas_role_dijalankan_ulang_pada_aksi_livewire(): void
    {
        $this->assertContains(PastikanRole::class, Livewire::getPersistentMiddleware());
    }

    /**
     * @return list<Route>
     */
    private function ruteArea(string $awalan): array
    {
        return array_values(array_filter(
            Router::getRoutes()->getRoutes(),
            fn (Route $r) => str_starts_with((string) $r->getName(), $awalan) && $r->getName() !== 'nazir.publik' && in_array('GET', $r->methods(), true),
        ));
    }

    private function url(Route $route): string
    {
        $nilai = [
            'pengajuan' => $this->pengajuan,
            'tanah' => $this->pengajuan->tanahWakaf,
            'user' => $this->nazir,
        ];

        return route($route->getName(), array_intersect_key($nilai, array_flip($route->parameterNames())));
    }

    private function buatPetugas(string $state): User
    {
        return $state === 'kua'
            ? User::factory()->kua($this->kecamatan)->create()
            : User::factory()->{$state}()->create();
    }
}
