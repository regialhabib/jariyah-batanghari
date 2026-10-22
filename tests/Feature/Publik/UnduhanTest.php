<?php

namespace Tests\Feature\Publik;

use App\Models\Unduhan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Unduhan dokumen penting publik (UnduhanController).
 */
class UnduhanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_unduh_file_menambah_penghitung(): void
    {
        Storage::disk('public')->put('unduhan/formulir.pdf', '%PDF-1.4 isi formulir');
        $unduhan = Unduhan::factory()->create(['judul' => 'Formulir Permohonan', 'file' => 'unduhan/formulir.pdf']);

        $respons = $this->get(route('unduhan.download', $unduhan));

        $respons->assertOk()->assertDownload('formulir-permohonan.pdf');
        $this->assertSame('%PDF-1.4 isi formulir', $respons->streamedContent());
        $this->assertSame(1, $unduhan->fresh()->jumlah_unduh);

        $this->get(route('unduhan.download', $unduhan))->assertOk();
        $this->assertSame(2, $unduhan->fresh()->jumlah_unduh);
    }

    public function test_file_kosong_atau_hilang_404(): void
    {
        $tanpaFile = Unduhan::factory()->create(['file' => null]);
        $fileHilang = Unduhan::factory()->create(['file' => 'unduhan/tidak-ada.pdf']);

        $this->get(route('unduhan.download', $tanpaFile))->assertNotFound();
        $this->get(route('unduhan.download', $fileHilang))->assertNotFound();
        $this->get('/unduh/9999')->assertNotFound();

        $this->assertSame(0, $fileHilang->fresh()->jumlah_unduh);
    }

    public function test_unduh_semua_sebagai_zip(): void
    {
        Storage::disk('public')->put('unduhan/a.pdf', 'isi A');
        Storage::disk('public')->put('unduhan/b.docx', 'isi B');
        $a = Unduhan::factory()->create(['judul' => 'Formulir Permohonan', 'file' => 'unduhan/a.pdf', 'urutan' => 1]);
        $b = Unduhan::factory()->create(['judul' => 'Contoh Surat Kuasa', 'file' => 'unduhan/b.docx', 'urutan' => 2]);
        Unduhan::factory()->create(['judul' => 'Belum Ada', 'file' => null, 'urutan' => 3]);
        Unduhan::factory()->create(['judul' => 'File Hilang', 'file' => 'unduhan/hilang.pdf', 'urutan' => 4]);

        $respons = $this->get(route('unduhan.semua'));

        $respons->assertOk()->assertDownload('dokumen-wakaf-batang-hari.zip');

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($respons->baseResponse->getFile()->getPathname()));
        $nama = collect(range(0, $zip->numFiles - 1))->map(fn (int $i) => $zip->getNameIndex($i))->all();
        $this->assertSame(['01-formulir-permohonan.pdf', '02-contoh-surat-kuasa.docx'], $nama);
        $this->assertSame('isi A', $zip->getFromName('01-formulir-permohonan.pdf'));
        $zip->close();
        @unlink($respons->baseResponse->getFile()->getPathname());

        $this->assertSame(1, $a->fresh()->jumlah_unduh);
        $this->assertSame(1, $b->fresh()->jumlah_unduh);
    }

    public function test_unduh_semua_tanpa_file_404(): void
    {
        Unduhan::factory()->create(['file' => null]);

        $this->get(route('unduhan.semua'))->assertNotFound();
    }
}
