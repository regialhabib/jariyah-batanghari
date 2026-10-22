<?php

namespace App\Exports\Laporan;

use App\Models\TanahWakaf;
use App\Support\FilterLaporan;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @implements WithMapping<TanahWakaf>
 */
class DataTanahSheet implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithFreezePane, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly FilterLaporan $filter) {}

    public function query(): Builder
    {
        return $this->filter->tanahTerurut();
    }

    public function title(): string
    {
        return 'Data Tanah Wakaf';
    }

    public function headings(): array
    {
        return ['No', ...FilterLaporan::judulKolom()];
    }

    private int $nomor = 0;

    public function map(mixed $row): array
    {
        return [++$this->nomor, ...array_values(FilterLaporan::baris($row))];
    }

    public function columnFormats(): array
    {
        return ['G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1];
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DCEFE2']]]];
    }
}
