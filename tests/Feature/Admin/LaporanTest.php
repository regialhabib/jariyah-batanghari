<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Exports\LaporanWakafExport;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use App\Support\FilterLaporan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Laporan + ekspor Excel/PDF (Fase 7). Halaman, Excel, dan PDF memakai FilterLaporan yang sama.
 */
class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private Kecamatan $kecA;

    private Kecamatan $kecB;

    private TanahWakaf $tanahA;

    private TanahWakaf $tanahB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kecA = Kecamatan::factory()->create(['nama' => 'Muara Bulian']);
        $this->kecB = Kecamatan::factory()->create(['nama' => 'Pemayung']);

        $this->tanahA = TanahWakaf::factory()->bersertifikat()->create([
            'nama' => 'Masjid Al-Ikhlas Bulian', 'desa_id' => Desa::factory()->create(['kecamatan_id' => $this->kecA->id])->id,
            'jenis_penggunaan' => JenisPenggunaan::Masjid, 'wakif_nik' => '1504111122223333', 'luas_m2' => 1000,
        ]);
        $this->tanahB = TanahWakaf::factory()->create([
            'nama' => 'Makam Umum Pemayung', 'desa_id' => Desa::factory()->create(['kecamatan_id' => $this->kecB->id])->id,
            'jenis_penggunaan' => JenisPenggunaan::Makam, 'wakif_nik' => '1504444455556666', 'luas_m2' => 500,
        ]);
    }

    private function kua(): User
    {
        return User::factory()->kua($this->kecA)->create();
    }

    public function test_halaman_laporan_dan_filter(): void
    {
        $bpn = User::factory()->bpn()->create();

        $this->actingAs($bpn)->get(route('admin.laporan'))
            ->assertOk()->assertSee('Masjid Al-Ikhlas Bulian')->assertSee('Makam Umum Pemayung')->assertSee('Ekspor Excel');

        $this->actingAs($bpn);
        Livewire::test('pages::admin.laporan')
            ->set('status', StatusSertifikat::Sudah->value)
            ->assertSee('Masjid Al-Ikhlas Bulian')
            ->assertDontSee('Makam Umum Pemayung')
            ->set('status', '')
            ->set('kecamatan', (string) $this->kecB->id)
            ->assertSee('Makam Umum Pemayung')
            ->assertDontSee('Masjid Al-Ikhlas Bulian')
            ->assertSee(route('admin.laporan.excel', ['kecamatan' => $this->kecB->id]), false);
    }

    public function test_kua_dikunci_ke_kecamatannya(): void
    {
        $this->actingAs($this->kua());

        Livewire::test('pages::admin.laporan')
            ->assertSet('kecamatan', (string) $this->kecA->id)
            ->assertSee('Masjid Al-Ikhlas Bulian')
            ->assertDontSee('Makam Umum Pemayung')
            ->set('kecamatan', (string) $this->kecB->id)
            ->assertSet('kecamatan', (string) $this->kecA->id)
            ->assertDontSee('Makam Umum Pemayung');
    }

    public function test_nazir_tidak_bisa_membuka_laporan(): void
    {
        $nazir = User::factory()->nazir()->create();

        $this->actingAs($nazir)->get(route('admin.laporan'))->assertForbidden();
        $this->actingAs($nazir)->get(route('admin.laporan.excel'))->assertForbidden();
        $this->actingAs($nazir)->get(route('admin.laporan.pdf'))->assertForbidden();
    }

    public function test_filter_periode_pengajuan(): void
    {
        $lama = Pengajuan::factory()->create(['tanah_wakaf_id' => $this->tanahA->id, 'status' => StatusPengajuan::Diajukan]);
        $lama->forceFill(['diajukan_at' => '2026-01-10 10:00:00'])->save();
        $baru = Pengajuan::factory()->create(['tanah_wakaf_id' => $this->tanahA->id, 'status' => StatusPengajuan::Ditolak]);
        $baru->forceFill(['diajukan_at' => '2026-06-15 10:00:00'])->save();

        $filter = FilterLaporan::dari(User::factory()->bpn()->create(), ['dari' => '2026-06-01', 'sampai' => '2026-06-30']);
        $rekap = $filter->rekapPengajuan();

        $this->assertSame(0, $rekap[StatusPengajuan::Diajukan->value]);
        $this->assertSame(1, $rekap[StatusPengajuan::Ditolak->value]);

        // Tanggal tidak sah diabaikan.
        $semua = FilterLaporan::dari(User::factory()->bpn()->create(), ['dari' => '2026-13-45', 'sampai' => 'kemarin']);
        $this->assertNull($semua->dari);
        $this->assertSame(2, array_sum($semua->rekapPengajuan()));
    }

    public function test_ekspor_excel_memakai_filter(): void
    {
        Excel::fake();
        $this->actingAs(User::factory()->bpn()->create())
            ->get(route('admin.laporan.excel', ['kecamatan' => $this->kecB->id]))
            ->assertOk();

        Excel::assertDownloaded(FilterLaporan::namaFile('xlsx'), function (LaporanWakafExport $export) {
            $kode = $export->filter->tanahTerurut()->pluck('kode')->all();

            return $kode === [$this->tanahB->kode] && count($export->sheets()) === 2;
        });
    }

    public function test_ekspor_excel_kua_hanya_kecamatannya_tanpa_nik(): void
    {
        // Parameter kecamatan lain diabaikan untuk KUA.
        $respons = $this->actingAs($this->kua())->get(route('admin.laporan.excel', ['kecamatan' => $this->kecB->id]));

        $respons->assertOk()->assertDownload(FilterLaporan::namaFile('xlsx'));
        $this->assertStringContainsString('spreadsheetml', $respons->headers->get('content-type'));

        $buku = IOFactory::load($respons->baseResponse->getFile()->getPathname());
        $this->assertSame(['Data Tanah Wakaf', 'Rekap per Kecamatan'], $buku->getSheetNames());

        $data = $buku->getSheetByName('Data Tanah Wakaf')->toArray();
        $isi = json_encode($data);
        $this->assertCount(2, $data); // judul kolom + 1 baris
        $this->assertStringContainsString('Masjid Al-Ikhlas Bulian', $isi);
        $this->assertStringNotContainsString('Makam Umum Pemayung', $isi);
        $this->assertStringNotContainsString('1504111122223333', $isi);
        $this->assertStringNotContainsString('NIK', $isi);

        $rekap = json_encode($buku->getSheetByName('Rekap per Kecamatan')->toArray());
        $this->assertStringContainsString('Muara Bulian', $rekap);
        $this->assertStringNotContainsString('Pemayung', $rekap);
    }

    public function test_ekspor_pdf(): void
    {
        $respons = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.laporan.pdf', ['jenis' => JenisPenggunaan::Masjid->value]));

        $respons->assertOk()->assertDownload(FilterLaporan::namaFile('pdf'));
        $this->assertSame('application/pdf', $respons->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $respons->getContent());
    }

    public function test_tampilan_pdf_kua_hanya_kecamatannya(): void
    {
        $kua = $this->kua();
        $filter = FilterLaporan::dari($kua, ['kecamatan' => (string) $this->kecB->id]);

        $html = view('pdf.laporan', [
            'filter' => $filter, 'ringkasan' => $filter->ringkasan(), 'rekap' => $filter->perKecamatan(),
            'rekapPengajuan' => $filter->rekapPengajuan(), 'tanahs' => $filter->tanahTerurut()->get(),
            'dicetak' => now(), 'pencetak' => $kua,
        ])->render();

        $this->assertStringContainsString('KANTOR PERTANAHAN KABUPATEN BATANG HARI', $html);
        $this->assertStringContainsString('Masjid Al-Ikhlas Bulian', $html);
        $this->assertStringNotContainsString('Makam Umum Pemayung', $html);
        $this->assertStringNotContainsString('1504111122223333', $html);
        $this->assertStringContainsString('Kecamatan: Muara Bulian', $html);
    }
}
