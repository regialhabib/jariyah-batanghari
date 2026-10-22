<?php

namespace App\Exports\Laporan;

use App\Support\FilterLaporan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapKecamatanSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    private int $jumlahBaris = 0;

    public function __construct(private readonly FilterLaporan $filter) {}

    public function title(): string
    {
        return 'Rekap per Kecamatan';
    }

    public function headings(): array
    {
        return ['No', 'Kecamatan', 'Sudah Bersertifikat', 'Dalam Proses', 'Belum Bersertifikat', 'Total Bidang', 'Total Luas (m²)'];
    }

    public function array(): array
    {
        $rekap = $this->filter->perKecamatan();

        $baris = $rekap->values()->map(fn (array $r, int $i) => [
            $i + 1, $r['kecamatan'], $r['sudah'], $r['proses'], $r['belum'], $r['total'], round($r['luas_m2'], 2),
        ])->all();

        $baris[] = [
            '', 'JUMLAH', $rekap->sum('sudah'), $rekap->sum('proses'), $rekap->sum('belum'), $rekap->sum('total'), round($rekap->sum('luas_m2'), 2),
        ];

        $baris[] = [];
        foreach ($this->filter->uraian() as $uraian) {
            $baris[] = ['', $uraian];
        }

        $this->jumlahBaris = $rekap->count();

        return $baris;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DCEFE2']]],
            $this->jumlahBaris + 2 => ['font' => ['bold' => true]],
        ];
    }
}
