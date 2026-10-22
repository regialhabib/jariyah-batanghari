<?php

namespace Tests\Feature\Workflow;

use App\Enums\JenisPengajuan;
use App\Enums\JenisPenggunaan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Enums\StatusValidasiDokumen;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use App\Notifications\PengajuanMasuk;
use App\Notifications\StatusPengajuanBerubah;
use App\Services\PengajuanWorkflow;
use Database\Seeders\PersyaratanSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PengajuanWorkflowTest extends TestCase
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

        $this->seed([PersyaratanSeeder::class]);
        Storage::fake('local');
        Notification::fake();

        $this->workflow = app(PengajuanWorkflow::class);
        $this->kecamatan = Kecamatan::factory()->create();
        $this->desa = Desa::factory()->for($this->kecamatan)->create();
        $this->nazir = User::factory()->nazir()->create();
        $this->kua = User::factory()->kua($this->kecamatan)->create();
        $this->bpn = User::factory()->bpn()->create();
    }

    public function test_alur_aiw_lengkap_sampai_disahkan(): void
    {
        $pengajuan = $this->draftAiw();
        $this->assertFalse($pengajuan->tanahWakaf->is_published);

        $this->unggahSemua($pengajuan);
        $this->workflow->kirim($pengajuan, $this->nazir);

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::Diajukan, $pengajuan->status);
        $this->assertMatchesRegularExpression('/^JRY-\d{4}-0001$/', $pengajuan->nomor);
        $this->assertNotNull($pengajuan->diajukan_at);

        $this->validasiSemua($pengajuan, $this->kua);
        $this->workflow->sahkanAiw($pengajuan, $this->kua, 'W.2/123/2026', now(), UploadedFile::fake()->create('aiw.pdf', 100, 'application/pdf'));

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::AiwDisahkan, $pengajuan->status);
        $this->assertNotNull($pengajuan->selesai_at);
        $this->assertSame('W.2/123/2026', $pengajuan->tanahWakaf->nomor_aiw);
        $this->assertTrue($pengajuan->tanahWakaf->is_published);
        $this->assertSame(StatusSertifikat::Belum, $pengajuan->tanahWakaf->status_sertifikat);
        $this->assertSame(2, $pengajuan->riwayats()->count());

        Notification::assertSentTo($this->nazir, StatusPengajuanBerubah::class);
        Notification::assertSentTo($this->kua, PengajuanMasuk::class);
        Notification::assertNotSentTo($this->bpn, PengajuanMasuk::class);
    }

    public function test_tidak_bisa_kirim_jika_dokumen_wajib_belum_lengkap(): void
    {
        $pengajuan = $this->draftAiw();

        $this->expectException(ValidationException::class);
        $this->workflow->kirim($pengajuan, $this->nazir);
    }

    public function test_kua_kecamatan_lain_dan_bpn_tidak_boleh_memeriksa_aiw(): void
    {
        $pengajuan = $this->draftAiwTerkirim();
        $kuaLain = User::factory()->kua()->create();

        $this->assertFalse($kuaLain->can('periksa', $pengajuan));
        $this->assertFalse($kuaLain->can('view', $pengajuan));
        $this->assertFalse($this->bpn->can('periksa', $pengajuan));
        $this->assertTrue($this->kua->can('periksa', $pengajuan));

        $this->expectException(AuthorizationException::class);
        $this->workflow->mintaPerbaikan($pengajuan, $kuaLain, 'Tidak berwenang');
    }

    public function test_tidak_bisa_sahkan_aiw_jika_ada_dokumen_belum_valid(): void
    {
        $pengajuan = $this->draftAiwTerkirim();

        $this->expectException(ValidationException::class);
        $this->workflow->sahkanAiw($pengajuan, $this->kua, 'W.2/1/2026', now());
    }

    public function test_perbaikan_dokumen_lalu_kirim_ulang(): void
    {
        $pengajuan = $this->draftAiwTerkirim();
        $dokumen = $pengajuan->dokumens()->first();

        $this->workflow->validasiDokumen($dokumen, $this->kua, StatusValidasiDokumen::TidakValid, 'Scan buram');
        $this->workflow->mintaPerbaikan($pengajuan, $this->kua, 'Perbaiki scan KTP');
        $this->assertSame(StatusPengajuan::PerluPerbaikan, $pengajuan->refresh()->status);

        // Belum diganti: kirim ulang ditolak.
        try {
            $this->workflow->kirim($pengajuan, $this->nazir);
            $this->fail('Kirim ulang seharusnya ditolak.');
        } catch (ValidationException) {
        }

        $this->workflow->unggahDokumen($pengajuan, $dokumen->persyaratan, UploadedFile::fake()->create('ktp.pdf', 50, 'application/pdf'), $this->nazir);
        $this->workflow->kirim($pengajuan, $this->nazir);

        $this->assertSame(StatusPengajuan::Diajukan, $pengajuan->refresh()->status);
        Storage::disk('local')->assertMissing($dokumen->path);
    }

    public function test_alur_sertifikat_dan_status_tanah(): void
    {
        $tanah = TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id]);
        $pengajuan = $this->workflow->buatDraf($this->nazir, JenisPengajuan::SertifikatBelum, $tanah);
        $this->unggahSemua($pengajuan);
        $this->workflow->kirim($pengajuan, $this->nazir);

        $this->assertSame(StatusSertifikat::Proses, $tanah->refresh()->status_sertifikat);
        $this->assertFalse($this->kua->can('periksa', $pengajuan), 'KUA tidak boleh memverifikasi sertifikat');

        $this->workflow->mintaPerbaikan($pengajuan, $this->bpn, 'Lengkapi');
        $this->assertSame(StatusSertifikat::Proses, $tanah->refresh()->status_sertifikat);

        $this->workflow->kirim($pengajuan, $this->nazir);
        $this->validasiSemua($pengajuan, $this->bpn);
        $this->workflow->verifikasi($pengajuan, $this->bpn);
        $this->workflow->terbitkanSertifikat($pengajuan, $this->bpn, '10.04.01.02.1.00001', now());

        $tanah->refresh();
        $this->assertSame(StatusPengajuan::SertifikatTerbit, $pengajuan->refresh()->status);
        $this->assertSame(StatusSertifikat::Sudah, $tanah->status_sertifikat);
        $this->assertSame('10.04.01.02.1.00001', $tanah->nomor_sertifikat);
    }

    public function test_tolak_sertifikat_mengembalikan_status_tanah(): void
    {
        $tanah = TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id]);
        $pengajuan = $this->workflow->buatDraf($this->nazir, JenisPengajuan::SertifikatBersertifikat, $tanah);
        $this->unggahSemua($pengajuan);
        $this->workflow->kirim($pengajuan, $this->nazir);

        $this->workflow->tolak($pengajuan, $this->bpn, 'Tanah dalam sengketa');

        $this->assertSame(StatusPengajuan::Ditolak, $pengajuan->refresh()->status);
        $this->assertSame(StatusSertifikat::Belum, $tanah->refresh()->status_sertifikat);
    }

    public function test_transisi_tidak_sah_ditolak(): void
    {
        $tanah = TanahWakaf::factory()->milik($this->nazir)->create(['desa_id' => $this->desa->id]);
        $pengajuan = $this->workflow->buatDraf($this->nazir, JenisPengajuan::SertifikatBelum, $tanah);
        $this->unggahSemua($pengajuan);
        $this->workflow->kirim($pengajuan, $this->nazir);

        // Langsung terbit tanpa verifikasi: tidak sah.
        $this->expectException(ValidationException::class);
        $this->workflow->terbitkanSertifikat($pengajuan, $this->bpn, 'X', now());
    }

    public function test_sertifikat_butuh_aiw_dan_tanah_milik_sendiri(): void
    {
        $tanpaAiw = TanahWakaf::factory()->milik($this->nazir)->tanpaAiw()->create(['desa_id' => $this->desa->id]);

        $this->expectException(ValidationException::class);
        $this->workflow->buatDraf($this->nazir, JenisPengajuan::SertifikatBelum, $tanpaAiw);
    }

    public function test_nazir_lain_tidak_bisa_melihat_atau_mengubah(): void
    {
        $pengajuan = $this->draftAiw();
        $lain = User::factory()->nazir()->create();

        $this->assertFalse($lain->can('view', $pengajuan));
        $this->assertFalse($lain->can('update', $pengajuan));
        $this->assertSame(0, Pengajuan::query()->forUser($lain)->count());
        $this->assertSame(1, Pengajuan::query()->forUser($this->kua)->count());
    }

    public function test_status_bisa_ke(): void
    {
        $this->assertTrue(StatusPengajuan::Diajukan->bisaKe(StatusPengajuan::AiwDisahkan, JenisPengajuan::Aiw));
        $this->assertFalse(StatusPengajuan::Diajukan->bisaKe(StatusPengajuan::Terverifikasi, JenisPengajuan::Aiw));
        $this->assertFalse(StatusPengajuan::Diajukan->bisaKe(StatusPengajuan::AiwDisahkan, JenisPengajuan::SertifikatBelum));
        $this->assertTrue(StatusPengajuan::Terverifikasi->bisaKe(StatusPengajuan::SertifikatTerbit, JenisPengajuan::SertifikatBelum));
        $this->assertSame([], StatusPengajuan::Ditolak->tujuanSah(JenisPengajuan::Aiw));
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

    private function validasiSemua(Pengajuan $pengajuan, User $pemeriksa): void
    {
        foreach ($pengajuan->dokumens as $dokumen) {
            $this->workflow->validasiDokumen($dokumen, $pemeriksa, StatusValidasiDokumen::Valid);
        }
    }
}
