<?php

namespace App\Http\Controllers;

use App\Models\PengajuanDokumen;
use App\Services\PengajuanWorkflow;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Membuka dokumen pengajuan dari disk privat setelah dicek Policy.
 */
class DokumenController extends Controller
{
    public function show(PengajuanDokumen $dokumen): StreamedResponse
    {
        Gate::authorize('view', $dokumen);

        $disk = Storage::disk(PengajuanWorkflow::DISK);
        abort_unless($disk->exists($dokumen->path), 404);

        $disposisi = request()->boolean('unduh') ? 'attachment' : 'inline';

        return $disk->response($dokumen->path, $dokumen->nama_asli, [
            'Content-Type' => $dokumen->mime ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ], $disposisi);
    }
}
