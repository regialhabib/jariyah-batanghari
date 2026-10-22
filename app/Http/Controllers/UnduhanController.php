<?php

namespace App\Http\Controllers;

use App\Models\Unduhan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Unduhan formulir / dokumen penting publik (disk `public`).
 */
class UnduhanController extends Controller
{
    public function show(Unduhan $unduhan): StreamedResponse
    {
        abort_unless($this->fileAda($unduhan), 404);

        $unduhan->increment('jumlah_unduh');

        return Storage::disk('public')->download($unduhan->file, $this->namaFile($unduhan));
    }

    /**
     * Semua dokumen yang filenya tersedia, dikemas dalam satu ZIP.
     */
    public function semua(): BinaryFileResponse
    {
        /** @var Collection<int, Unduhan> $unduhans */
        $unduhans = Unduhan::query()
            ->whereNotNull('file')->where('file', '!=', '')
            ->orderBy('urutan')->orderBy('id')
            ->get()
            ->filter(fn (Unduhan $u) => $this->fileAda($u))
            ->values();

        abort_if($unduhans->isEmpty(), 404);

        $path = tempnam(sys_get_temp_dir(), 'unduhan-');
        $zip = new ZipArchive;

        abort_unless($path !== false && $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        $disk = Storage::disk('public');

        foreach ($unduhans as $i => $unduhan) {
            $zip->addFromString(sprintf('%02d-%s', $i + 1, $this->namaFile($unduhan)), (string) $disk->get($unduhan->file));
        }

        $zip->close();

        Unduhan::query()->whereKey($unduhans->modelKeys())->increment('jumlah_unduh');

        return response()
            ->download($path, 'dokumen-wakaf-batang-hari.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    private function fileAda(Unduhan $unduhan): bool
    {
        return $unduhan->tersedia() && Storage::disk('public')->exists($unduhan->file);
    }

    /**
     * Nama file unduhan yang rapi, mis. "formulir-permohonan.pdf".
     */
    private function namaFile(Unduhan $unduhan): string
    {
        $ekstensi = pathinfo($unduhan->file, PATHINFO_EXTENSION);

        return (Str::slug($unduhan->judul) ?: 'dokumen').($ekstensi !== '' ? '.'.Str::lower($ekstensi) : '');
    }
}
