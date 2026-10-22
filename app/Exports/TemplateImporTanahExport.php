<?php

namespace App\Exports;

use App\Imports\TanahWakafImport;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template Excel impor tanah wakaf: judul kolom sesuai docs/08-kebutuhan-data.md + satu baris contoh.
 */
class TemplateImporTanahExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    /**
     * @return array<int, mixed>
     */
    public function headings(): array
    {
        return TanahWakafImport::KOLOM;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [[
            'Masjid Al-Ikhlas', 'Muara Bulian', 'Rengas Condong', 'Jl. Contoh No. 1, RT 01', 1200, 'masjid', 'H. Abdullah',
            'Ahmad Fauzi', 'sudah', 'W.2/12/2015', '2015-03-21', '10.04.01.02.1.00001', '2018-07-10', -1.7012345, 103.2654321,
        ]];
    }

    /**
     * @return array<int|string, array<string, mixed>>|null
     */
    public function styles(Worksheet $sheet): ?array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Data Tanah Wakaf';
    }
}
