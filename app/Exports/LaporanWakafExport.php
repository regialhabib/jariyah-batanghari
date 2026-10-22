<?php

namespace App\Exports;

use App\Exports\Laporan\DataTanahSheet;
use App\Exports\Laporan\RekapKecamatanSheet;
use App\Support\FilterLaporan;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Ekspor laporan tanah wakaf ke Excel: sheet "Data Tanah Wakaf" dan "Rekap per Kecamatan".
 * Data & cakupan user mengikuti FilterLaporan (sama dengan halaman Laporan dan PDF).
 */
class LaporanWakafExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function __construct(public readonly FilterLaporan $filter) {}

    public function sheets(): array
    {
        return [
            new DataTanahSheet($this->filter),
            new RekapKecamatanSheet($this->filter),
        ];
    }
}
