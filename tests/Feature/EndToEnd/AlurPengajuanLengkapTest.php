<?php

namespace Tests\Feature\EndToEnd;

use App\Enums\JenisPengajuan;
use App\Enums\JenisPenggunaan;
use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\Persyaratan;
use App\Models\User;
use App\Notifications\StatusPengajuanBerubah;
use Database\Seeders\PersyaratanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Skenario end-to-end docs/09-pengujian.md langkah 2–8, dijalankan lewat halaman yang dipakai pengguna:
 * registrasi Nazir → AIW → perbaikan → pengesahan KUA → sertifikat → verifikasi & terbit oleh BPN → peta publik.
 */
class AlurPengajuanLengkapTest extends TestCase
{
    use RefreshDatabase;

    public function test_alur_dari_registrasi_nazir_sampai_sertifikat_terbit(): void
    {
        Storage::fake('local');
        $this->seed(PersyaratanSeeder::class);

        $kecamatan = Kecamatan::factory()->create(['nama' => 'Muara Bulian']);
        $desa = Desa::factory()->create(['kecamatan_id' => $kecamatan->id]);
        $kua = User::factory()->kua($kecamatan)->create();
        $kuaLain = User::factory()->kua(Kecamatan::factory()->create())->create();
        $bpn = User::factory()->bpn()->create();

        // 2. Registrasi Nazir dan verifikasi email.
        $this->post(route('register.store'), [
            'name' => 'Ahmad Nazir',
            'nik' => '1504012345678901',
            'email' => 'nazir.baru@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ])->assertSessionHasNoErrors();

        $nazir = User::query()->where('email', 'nazir.baru@example.com')->sole();
        $this->assertSame(Role::Nazir, $nazir->role);

        $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $nazir->id, 'hash' => sha1($nazir->email)]));
        $this->assertTrue($nazir->refresh()->hasVerifiedEmail());
        $this->get(route('dashboard'))->assertRedirect(route('nazir.dashboard'));

        // 3. Nazir membuat pengajuan AIW lewat wizard, mengunggah dokumen, lalu mengirim.
        Livewire::test('pages::nazir.pengajuan.buat')
            ->call('pilihJenis', JenisPengajuan::Aiw->value)
            ->call('lanjut')
            ->set('nama', 'Masjid Al-Ikhlas')
            ->set('jenis_penggunaan', JenisPenggunaan::Masjid->value)
            ->set('wakif_nama', 'H. Abdullah')
            ->set('kecamatan_id', (string) $kecamatan->id)
            ->set('desa_id', (string) $desa->id)
            ->set('luas_m2', '800')
            ->call('lanjut')
            ->set('latitude', -1.7)
            ->set('longitude', 103.27)
            ->set('batas_geojson', ['type' => 'Polygon', 'coordinates' => [[[103.27, -1.7], [103.28, -1.7], [103.28, -1.71], [103.27, -1.7]]]])
            ->call('lanjut')
            ->call('simpan')
            ->assertHasNoErrors();

        $aiw = Pengajuan::query()->where('nazir_id', $nazir->id)->sole();
        $this->unggahSemuaDokumen($aiw);
        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $aiw])->call('kirim')->assertHasNoErrors();
        $this->assertSame(StatusPengajuan::Diajukan, $aiw->refresh()->status);

        // 4. KUA kecamatan lain tidak bisa membuka; KUA Muara Bulian menandai dokumen tidak valid dan minta perbaikan.
        $this->actingAs($kuaLain)->get(route('admin.pengajuan.show', $aiw))->assertForbidden();

        $this->actingAs($kua);
        $dokumenBuram = $aiw->dokumens()->firstOrFail();
        Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $aiw])
            ->call('bukaTidakValid', $dokumenBuram->id)
            ->set('catatanDokumen', 'Scan buram')
            ->call('tandaiTidakValid')
            ->set('catatanPerbaikan', 'Unggah ulang dokumen yang buram')
            ->call('mintaPerbaikan')
            ->assertHasNoErrors();
        $this->assertSame(StatusPengajuan::PerluPerbaikan, $aiw->refresh()->status);

        // 5. Nazir mengganti dokumen dan mengirim ulang; KUA mengesahkan AIW.
        $this->actingAs($nazir);
        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $aiw])
            ->set("berkas.{$dokumenBuram->persyaratan_id}", UploadedFile::fake()->create('jelas.pdf', 50, 'application/pdf'))
            ->call('kirim')
            ->assertHasNoErrors();
        $this->assertSame(StatusPengajuan::Diajukan, $aiw->refresh()->status);

        $this->actingAs($kua);
        $halamanKua = Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $aiw]);
        foreach ($aiw->dokumens()->get() as $dokumen) {
            $halamanKua->call('tandaiValid', $dokumen->id);
        }
        $halamanKua->set('nomorAiw', 'W.2/01/2026')
            ->set('tanggalAiw', now()->format('Y-m-d'))
            ->set('scanAiw', UploadedFile::fake()->create('aiw.pdf', 100, 'application/pdf'))
            ->call('sahkanAiw')
            ->assertHasNoErrors();

        $tanah = $aiw->refresh()->tanahWakaf;
        $this->assertSame(StatusPengajuan::AiwDisahkan, $aiw->status);
        $this->assertSame(StatusSertifikat::Belum, $tanah->status_sertifikat);
        $this->assertSame(StatusSertifikat::Belum->value, $this->statusDiPetaPublik($tanah->kode));

        // 6. Nazir mengajukan sertifikat: status tanah menjadi proses.
        $this->actingAs($nazir);
        Livewire::withQueryParams(['jenis' => JenisPengajuan::SertifikatBelum->value, 'tanah' => $tanah->id])
            ->test('pages::nazir.pengajuan.buat')
            ->call('lanjut')
            ->call('simpan')
            ->assertHasNoErrors();

        $sertifikat = Pengajuan::query()->where('jenis', JenisPengajuan::SertifikatBelum->value)->sole();
        $this->unggahSemuaDokumen($sertifikat);
        Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $sertifikat])->call('kirim')->assertHasNoErrors();
        $this->assertSame(StatusSertifikat::Proses->value, $this->statusDiPetaPublik($tanah->kode));

        // KUA tidak berwenang memeriksa sertifikat.
        $this->actingAs($kua)->get(route('admin.pengajuan.show', $sertifikat))->assertOk()->assertDontSee('Catat Sertifikat Terbit');

        // 7. BPN memverifikasi lalu mencatat sertifikat terbit: peta hijau, statistik beranda bertambah.
        $this->actingAs($bpn);
        $halamanBpn = Livewire::test('pages::admin.pengajuan.show', ['pengajuan' => $sertifikat]);
        foreach ($sertifikat->dokumens()->get() as $dokumen) {
            $halamanBpn->call('tandaiValid', $dokumen->id);
        }
        $halamanBpn->call('verifikasi')
            ->set('nomorSertifikat', '10.04.01.02.1.00123')
            ->set('tanggalSertifikat', now()->format('Y-m-d'))
            ->call('terbitkanSertifikat')
            ->assertHasNoErrors();

        $this->assertSame(StatusPengajuan::SertifikatTerbit, $sertifikat->refresh()->status);
        $this->assertSame(StatusSertifikat::Sudah->value, $this->statusDiPetaPublik($tanah->kode));
        $this->assertSame(1, Livewire::test('pages::publik.beranda')->instance()->statistik[1]['value']);

        // 8. Nazir menerima notifikasi pada setiap perubahan status.
        $this->assertEqualsCanonicalizing(
            ['perlu_perbaikan', 'aiw_disahkan', 'terverifikasi', 'sertifikat_terbit'],
            $nazir->notifications()->where('type', StatusPengajuanBerubah::class)->get()->pluck('data.status')->all(),
        );
    }

    private function unggahSemuaDokumen(Pengajuan $pengajuan): void
    {
        $halaman = Livewire::test('pages::nazir.pengajuan.show', ['pengajuan' => $pengajuan]);

        foreach (Persyaratan::query()->untuk($pengajuan->jenis)->get() as $syarat) {
            $halaman->set("berkas.{$syarat->id}", UploadedFile::fake()->create("syarat-{$syarat->id}.pdf", 50, 'application/pdf'))
                ->assertHasNoErrors();
        }
    }

    private function statusDiPetaPublik(string $kode): ?string
    {
        $fitur = collect($this->getJson(route('peta.data'))->assertOk()->json('features'))
            ->firstWhere('properties.kode', $kode);

        return $fitur['properties']['status'] ?? null;
    }
}
