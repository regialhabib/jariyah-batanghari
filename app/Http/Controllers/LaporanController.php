<?php

namespace App\Http\Controllers;

use App\Exports\LaporanWakafExport;
use App\Support\FilterLaporan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ekspor laporan Excel & PDF. Filter dan cakupan user (KUA = kecamatannya) dari FilterLaporan,
 * sama persis dengan halaman Laporan. NIK wakif tidak pernah ikut diekspor.
 */
class LaporanController extends Controller
{
    public function excel(Request $request): Response
    {
        $filter = $this->filter($request);

        return Excel::download(new LaporanWakafExport($filter), FilterLaporan::namaFile('xlsx'));
    }

    public function pdf(Request $request): Response
    {
        $filter = $this->filter($request);

        $pdf = Pdf::loadView('pdf.laporan', [
            'filter' => $filter,
            'ringkasan' => $filter->ringkasan(),
            'rekap' => $filter->perKecamatan(),
            'rekapPengajuan' => $filter->rekapPengajuan(),
            'tanahs' => $filter->tanahTerurut()->get(),
            'dicetak' => now(),
            'pencetak' => $request->user(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(FilterLaporan::namaFile('pdf'));
    }

    private function filter(Request $request): FilterLaporan
    {
        $user = $request->user();
        abort_unless($user?->can('laporan.ekspor'), 403);

        return FilterLaporan::dari($user, $request->only(FilterLaporan::KUNCI));
    }
}
