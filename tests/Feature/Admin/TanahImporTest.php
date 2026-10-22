<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Exports\TemplateImporTanahExport;
use App\Imports\TanahWakafImport;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelTipe;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TanahImporTest extends TestCase
{
    use RefreshDatabase;

    private User $bpn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $kecamatan = Kecamatan::factory()->create(['nama' => 'Muara Bulian']);
        Desa::factory()->for($kecamatan)->create(['nama' => 'Rengas Condong', 'jenis' => 'kelurahan']);
        Desa::factory()->for($kecamatan)->create(['nama' => 'Rambahan']);
        Desa::factory()->for(Kecamatan::factory()->create(['nama' => 'Pemayung']))->create(['nama' => 'Selat']);

        $this->bpn = User::factory()->bpn()->create();
    }

    /**
     * @param  list<list<mixed>>  $baris
     */
    private function csv(array $baris): UploadedFile
    {
        $isi = implode(',', TanahWakafImport::KOLOM)."\n";
        foreach ($baris as $b) {
            $isi .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $b))."\n";
        }

        return UploadedFile::fake()->createWithContent('data.csv', $isi);
    }

    /**
     * @return list<list<mixed>>
     */
    private function contohBaris(): array
    {
        return [
            // valid: label jenis & status, kecamatan/desa beda huruf besar, awalan "Kel."
            ['Masjid Al-Ikhlas', 'muara bulian', 'Kel. Rengas Condong', 'Jl. Sudirman', '1200', 'Masjid', 'H. Abdullah', 'Ahmad', 'Sudah Bersertifikat', 'W.2/1/2015', '2015-03-21', '10.04.01', '21/07/2018', '-1.7012', '103.2654'],
            // valid: nilai enum
            ['Makam Umum', 'Muara Bulian', 'Rambahan', '', '500,5', 'makam', 'Siti', '', 'belum', '', '', '', '', '-1.71', '103.26'],
            // desa ada di kecamatan lain
            ['Mushola X', 'Muara Bulian', 'Selat', '', '300', 'mushola', 'Budi', '', 'belum', '', '', '', '', '-1.7', '103.2'],
            // kecamatan tidak dikenal, jenis salah, luas salah, koordinat kosong
            ['', 'Antah Berantah', 'Desa X', '', 'abc', 'kolam', '', '', 'entah', '', '31/02/2020', '', '', '', ''],
        ];
    }

    public function test_impor_csv_membuat_baris_valid_dan_melaporkan_galat(): void
    {
        $this->actingAs($this->bpn);

        $komponen = Livewire::test('pages::admin.tanah.impor')
            ->set('berkas', $this->csv($this->contohBaris()))
            ->call('periksa')
            ->assertHasNoErrors()
            ->assertSet('ringkasan', ['total' => 4, 'valid' => 2, 'galat' => 2])
            ->assertSee('Baris 4')
            ->assertSee('tidak ditemukan di Kecamatan Muara Bulian')
            ->assertSee('Kecamatan &quot;Antah Berantah&quot; tidak ditemukan.', false)
            ->assertSee('Jenis penggunaan &quot;kolam&quot; tidak dikenal.', false)
            ->assertSee('Latitude wajib');

        $this->assertSame(0, TanahWakaf::count(), 'Pemeriksaan tidak boleh menyimpan data.');

        $komponen->call('impor')->assertHasNoErrors()->assertSet('jumlahDiimpor', 2);

        $this->assertSame(2, TanahWakaf::count());
        $masjid = TanahWakaf::query()->where('nama', 'Masjid Al-Ikhlas')->firstOrFail();
        $this->assertSame('impor', $masjid->sumber);
        $this->assertTrue($masjid->is_published);
        $this->assertSame(JenisPenggunaan::Masjid, $masjid->jenis_penggunaan);
        $this->assertSame(StatusSertifikat::Sudah, $masjid->status_sertifikat);
        $this->assertSame('Rengas Condong', $masjid->desa->nama);
        $this->assertSame('2018-07-21', $masjid->tanggal_sertifikat->format('Y-m-d'));
        $this->assertSame('Ahmad', $masjid->nama_nazir);

        $makam = TanahWakaf::query()->where('nama', 'Makam Umum')->firstOrFail();
        $this->assertEqualsWithDelta(500.5, (float) $makam->luas_m2, 0.001);
        $this->assertSame(StatusSertifikat::Belum, $makam->status_sertifikat);
    }

    public function test_impor_xlsx(): void
    {
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([TanahWakafImport::KOLOM, [
            'Pesantren Darussalam', 'Pemayung', 'Selat', 'Jl. A', 2500, 'Pondok Pesantren', 'H. Umar', 'Ust. Ali', 'proses',
            'W.2/9/2019', '2019-05-01', '', '', -1.64, 103.42,
        ]]);
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($sheet))->save($path);

        $this->actingAs($this->bpn);
        Livewire::test('pages::admin.tanah.impor')
            ->set('berkas', UploadedFile::fake()->createWithContent('data.xlsx', (string) file_get_contents($path)))
            ->call('periksa')
            ->assertSet('ringkasan.valid', 1)
            ->call('impor')
            ->assertHasNoErrors();

        $tanah = TanahWakaf::query()->firstOrFail();
        $this->assertSame(JenisPenggunaan::Pesantren, $tanah->jenis_penggunaan);
        $this->assertSame(StatusSertifikat::Proses, $tanah->status_sertifikat);
        $this->assertSame('Selat', $tanah->desa->nama);
        @unlink($path);
    }

    public function test_berkas_tanpa_kolom_wajib_ditolak(): void
    {
        $this->actingAs($this->bpn);
        Livewire::test('pages::admin.tanah.impor')
            ->set('berkas', UploadedFile::fake()->createWithContent('data.csv', "judul,isi\nA,B\n"))
            ->call('periksa')
            ->assertHasErrors('berkas')
            ->assertSet('ringkasan', null);
    }

    public function test_template_bisa_diunduh_dan_sesuai_kolom(): void
    {
        Excel::fake();
        $this->actingAs($this->bpn);

        Livewire::test('pages::admin.tanah.impor')->call('unduhTemplate');

        Excel::assertDownloaded('template-impor-tanah-wakaf.xlsx', function (TemplateImporTanahExport $export) {
            return $export->headings() === TanahWakafImport::KOLOM;
        });
    }

    public function test_baris_contoh_template_valid(): void
    {
        $raw = (new TemplateImporTanahExport)->raw(ExcelTipe::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        file_put_contents($path, $raw);

        $import = new TanahWakafImport;
        Excel::import($import, $path);

        $this->assertNull($import->galatBerkas());
        $this->assertCount(1, $import->barisValid(), json_encode($import->barisGalat()));
        @unlink($path);
    }

    public function test_kua_tidak_bisa_impor(): void
    {
        $kua = User::factory()->kua()->create();

        $this->actingAs($kua)->get(route('admin.tanah.impor'))->assertForbidden();
        Livewire::test('pages::admin.tanah.impor')->assertForbidden();
    }
}
