<?php

namespace App\Imports;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Impor data tanah wakaf dari Excel/CSV (template: docs/08-kebutuhan-data.md).
 *
 * Membaca semua baris, memvalidasi per baris (kecamatan/desa dicocokkan dengan nama, jenis & status
 * dengan nilai atau label), lalu {@see simpan()} membuat baris yang valid dengan sumber `impor`
 * dan langsung dipublikasikan. Baris bermasalah dilaporkan lewat {@see barisGalat()}.
 */
class TanahWakafImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public const KOLOM = [
        'nama', 'kecamatan', 'desa', 'alamat', 'luas_m2', 'jenis_penggunaan', 'wakif_nama', 'nama_nazir',
        'status_sertifikat', 'nomor_aiw', 'tanggal_aiw', 'nomor_sertifikat', 'tanggal_sertifikat', 'latitude', 'longitude',
    ];

    public const KOLOM_WAJIB = ['nama', 'kecamatan', 'desa', 'luas_m2', 'jenis_penggunaan', 'wakif_nama', 'latitude', 'longitude'];

    public const MAKS_BARIS = 2000;

    /**
     * @var list<array{baris: int, nama: string, data: array<string, mixed>|null, galat: list<string>}>
     */
    private array $hasil = [];

    /**
     * @var list<string>
     */
    private array $kolomHilang = [];

    private bool $terlaluBanyak = false;

    /**
     * @var array<string, int>|null nama kecamatan ternormalisasi => id
     */
    private ?array $kecamatans = null;

    /**
     * @var array<int, array<string, int>> kecamatan_id => [nama desa ternormalisasi => id]
     */
    private array $desas = [];

    /**
     * Nomor AIW/sertifikat yang sudah terpakai: di database ("TW-01-0001 · Nama") atau di baris
     * sebelumnya dalam berkas yang sama ("baris 5"). Kunci = nomor huruf kecil.
     *
     * @var array<'nomor_aiw'|'nomor_sertifikat', array<string, string>>|null
     */
    private ?array $nomorTerpakai = null;

    public function collection(Collection $rows): void
    {
        $this->hasil = [];
        $this->nomorTerpakai = null;

        if ($rows->isEmpty()) {
            return;
        }

        $kolom = array_map('strval', array_keys($rows->first()->toArray()));
        $this->kolomHilang = array_values(array_diff(self::KOLOM_WAJIB, $kolom));
        if ($this->kolomHilang) {
            return;
        }

        if ($rows->count() > self::MAKS_BARIS) {
            $this->terlaluBanyak = true;

            return;
        }

        foreach ($rows->values() as $i => $row) {
            // Baris 1 = judul kolom, data mulai baris 2.
            $this->hasil[] = $this->periksaBaris($row->toArray(), $i + 2);
        }
    }

    /**
     * @return list<array{baris: int, nama: string, data: array<string, mixed>|null, galat: list<string>}>
     */
    public function hasil(): array
    {
        return $this->hasil;
    }

    /**
     * @return list<array{baris: int, nama: string, data: array<string, mixed>|null, galat: list<string>}>
     */
    public function barisValid(): array
    {
        return array_values(array_filter($this->hasil, fn ($h) => $h['galat'] === []));
    }

    /**
     * @return list<array{baris: int, nama: string, data: array<string, mixed>|null, galat: list<string>}>
     */
    public function barisGalat(): array
    {
        return array_values(array_filter($this->hasil, fn ($h) => $h['galat'] !== []));
    }

    /**
     * Galat tingkat berkas (kolom wajib hilang, berkas kosong, terlalu banyak baris).
     */
    public function galatBerkas(): ?string
    {
        if ($this->kolomHilang) {
            return 'Kolom wajib tidak ditemukan: '.implode(', ', $this->kolomHilang).'. Gunakan template impor yang disediakan.';
        }

        if ($this->terlaluBanyak) {
            return 'Berkas berisi lebih dari '.self::MAKS_BARIS.' baris. Pecah menjadi beberapa berkas.';
        }

        if ($this->hasil === []) {
            return 'Berkas tidak berisi baris data.';
        }

        return null;
    }

    /**
     * Menyimpan semua baris yang valid. Mengembalikan jumlah baris yang dibuat.
     */
    public function simpan(): int
    {
        $valid = $this->barisValid();

        DB::transaction(function () use ($valid) {
            foreach ($valid as $baris) {
                TanahWakaf::create([
                    ...$baris['data'],
                    'is_published' => true,
                    'sumber' => 'impor',
                ]);
            }
        });

        return count($valid);
    }

    /**
     * @param  array<array-key, mixed>  $row
     * @return array{baris: int, nama: string, data: array<string, mixed>|null, galat: list<string>}
     */
    private function periksaBaris(array $row, int $nomorBaris): array
    {
        $teks = fn (string $kolom) => trim((string) ($row[$kolom] ?? ''));
        $galat = [];

        $nama = $teks('nama');
        if ($nama === '') {
            $galat[] = 'Nama wajib diisi.';
        } elseif (mb_strlen($nama) > 255) {
            $galat[] = 'Nama maksimal 255 karakter.';
        }

        $wakif = $teks('wakif_nama');
        if ($wakif === '') {
            $galat[] = 'Nama wakif wajib diisi.';
        } elseif (mb_strlen($wakif) > 255) {
            $galat[] = 'Nama wakif maksimal 255 karakter.';
        }

        $desaId = null;
        $kecamatanId = $this->cariKecamatan($teks('kecamatan'));
        if ($teks('kecamatan') === '') {
            $galat[] = 'Kecamatan wajib diisi.';
        } elseif (! $kecamatanId) {
            $galat[] = "Kecamatan \"{$teks('kecamatan')}\" tidak ditemukan.";
        } elseif ($teks('desa') === '') {
            $galat[] = 'Desa wajib diisi.';
        } else {
            $desaId = $this->cariDesa($kecamatanId, $teks('desa'));
            if (! $desaId) {
                $galat[] = "Desa \"{$teks('desa')}\" tidak ditemukan di Kecamatan {$teks('kecamatan')}.";
            }
        }

        $luas = $this->angka($row['luas_m2'] ?? null);
        if ($luas === null || $luas <= 0) {
            $galat[] = 'Luas (m²) wajib berupa angka lebih dari 0.';
        }

        $jenis = $this->cocokkanEnum(JenisPenggunaan::cases(), $teks('jenis_penggunaan'), [
            'pondok pesantren' => JenisPenggunaan::Pesantren, 'sekolah' => JenisPenggunaan::Sekolah,
            'madrasah' => JenisPenggunaan::Sekolah, 'musholla' => JenisPenggunaan::Mushola, 'musala' => JenisPenggunaan::Mushola,
            'kuburan' => JenisPenggunaan::Makam, 'pemakaman' => JenisPenggunaan::Makam,
        ]);
        if (! $jenis) {
            $galat[] = $teks('jenis_penggunaan') === ''
                ? 'Jenis penggunaan wajib diisi.'
                : "Jenis penggunaan \"{$teks('jenis_penggunaan')}\" tidak dikenal.";
        }

        $status = $teks('status_sertifikat') === ''
            ? StatusSertifikat::Belum
            : $this->cocokkanEnum(StatusSertifikat::cases(), $teks('status_sertifikat'), [
                'bersertifikat' => StatusSertifikat::Sudah, 'sudah bersertifikat' => StatusSertifikat::Sudah,
                'belum bersertifikat' => StatusSertifikat::Belum, 'dalam proses' => StatusSertifikat::Proses,
            ]);
        if (! $status) {
            $galat[] = "Status sertifikat \"{$teks('status_sertifikat')}\" tidak dikenal (gunakan belum, proses, atau sudah).";
        }

        $tanggalAiw = $this->tanggal($row['tanggal_aiw'] ?? null, 'Tanggal AIW', $galat);
        $tanggalSertifikat = $this->tanggal($row['tanggal_sertifikat'] ?? null, 'Tanggal sertifikat', $galat);

        if ($status === StatusSertifikat::Sudah && $teks('nomor_sertifikat') === '') {
            $galat[] = 'Nomor sertifikat wajib diisi untuk tanah yang sudah bersertifikat.';
        }

        foreach (['nomor_aiw' => 'Nomor AIW', 'nomor_sertifikat' => 'Nomor sertifikat', 'nama_nazir' => 'Nama Nazir'] as $kolom => $label) {
            if (mb_strlen($teks($kolom)) > 255) {
                $galat[] = "{$label} maksimal 255 karakter.";
            }
        }

        foreach (['nomor_aiw' => 'Nomor AIW', 'nomor_sertifikat' => 'Nomor sertifikat'] as $kolom => $label) {
            if ($pemakai = $this->pemakaiNomor($kolom, $teks($kolom), $nomorBaris)) {
                $galat[] = "{$label} \"{$teks($kolom)}\" sudah dipakai oleh {$pemakai}.";
            }
        }

        $lat = $this->angka($row['latitude'] ?? null);
        $lng = $this->angka($row['longitude'] ?? null);
        if ($lat === null || $lat < -90 || $lat > 90) {
            $galat[] = 'Latitude wajib berupa angka antara -90 dan 90.';
        }
        if ($lng === null || $lng < -180 || $lng > 180) {
            $galat[] = 'Longitude wajib berupa angka antara -180 dan 180.';
        }

        $data = $galat ? null : [
            'nama' => $nama,
            'desa_id' => $desaId,
            'alamat' => $teks('alamat') ?: null,
            'luas_m2' => $luas,
            'jenis_penggunaan' => $jenis,
            'wakif_nama' => $wakif,
            'nama_nazir' => $teks('nama_nazir') ?: null,
            'status_sertifikat' => $status,
            'nomor_aiw' => $teks('nomor_aiw') ?: null,
            'tanggal_aiw' => $tanggalAiw,
            'nomor_sertifikat' => $teks('nomor_sertifikat') ?: null,
            'tanggal_sertifikat' => $tanggalSertifikat,
            'latitude' => round($lat, 7),
            'longitude' => round($lng, 7),
        ];

        return ['baris' => $nomorBaris, 'nama' => $nama, 'data' => $data, 'galat' => $galat];
    }

    /**
     * Siapa yang sudah memakai nomor ini, atau null bila belum terpakai. Nomor yang lolos dicatat
     * agar baris berikutnya dengan nomor yang sama ditandai ganda.
     *
     * @param  'nomor_aiw'|'nomor_sertifikat'  $kolom
     */
    private function pemakaiNomor(string $kolom, string $nomor, int $nomorBaris): ?string
    {
        $kunci = mb_strtolower($nomor);
        if ($kunci === '') {
            return null;
        }

        $this->nomorTerpakai ??= collect(['nomor_aiw', 'nomor_sertifikat'])
            ->mapWithKeys(fn (string $k) => [$k => TanahWakaf::query()->whereNotNull($k)->get(['kode', 'nama', $k])
                ->mapWithKeys(fn (TanahWakaf $t) => [mb_strtolower(trim((string) $t->{$k})) => "{$t->kode} · {$t->nama}"])
                ->all()])
            ->all();

        if (isset($this->nomorTerpakai[$kolom][$kunci])) {
            return $this->nomorTerpakai[$kolom][$kunci];
        }

        $this->nomorTerpakai[$kolom][$kunci] = "baris {$nomorBaris} di berkas ini";

        return null;
    }

    private function normal(string $nama, string $awalan = ''): string
    {
        $nama = Str::of($nama)->lower()->squish()->toString();

        if ($awalan !== '') {
            $nama = (string) preg_replace('/^('.$awalan.')\s*/u', '', $nama);
        }

        return $nama;
    }

    private function cariKecamatan(string $nama): ?int
    {
        if ($nama === '') {
            return null;
        }

        $this->kecamatans ??= Kecamatan::query()->pluck('id', 'nama')
            ->mapWithKeys(fn ($id, $nm) => [$this->normal((string) $nm, 'kecamatan|kec\.') => (int) $id])
            ->all();

        return $this->kecamatans[$this->normal($nama, 'kecamatan|kec\.')] ?? null;
    }

    private function cariDesa(int $kecamatanId, string $nama): ?int
    {
        $awalan = 'desa|kelurahan|kel\.';

        $this->desas[$kecamatanId] ??= Desa::query()->where('kecamatan_id', $kecamatanId)->pluck('id', 'nama')
            ->mapWithKeys(fn ($id, $nm) => [$this->normal((string) $nm, $awalan) => (int) $id])
            ->all();

        return $this->desas[$kecamatanId][$this->normal($nama, $awalan)] ?? null;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  list<T>  $kasus
     * @param  array<string, T>  $alias
     * @return T|null
     */
    private function cocokkanEnum(array $kasus, string $nilai, array $alias = []): ?\BackedEnum
    {
        $nilai = $this->normal($nilai);
        if ($nilai === '') {
            return null;
        }

        foreach ($kasus as $k) {
            if ($nilai === strtolower((string) $k->value) || $nilai === $this->normal($k->label())) {
                return $k;
            }
        }

        return $alias[$nilai] ?? null;
    }

    private function angka(mixed $nilai): ?float
    {
        if (is_int($nilai) || is_float($nilai)) {
            return (float) $nilai;
        }

        $nilai = str_replace(' ', '', trim((string) $nilai));
        if ($nilai === '') {
            return null;
        }

        // Format Indonesia "1.500,25" atau "-1,7".
        if (str_contains($nilai, ',')) {
            $nilai = str_replace(['.', ','], ['', '.'], $nilai);
        }

        return is_numeric($nilai) ? (float) $nilai : null;
    }

    /**
     * @param  list<string>  $galat
     */
    private function tanggal(mixed $nilai, string $label, array &$galat): ?string
    {
        if ($nilai === null || trim((string) $nilai) === '') {
            return null;
        }

        $tanggal = null;
        $teks = trim((string) $nilai);

        if (is_numeric($nilai)) {
            try {
                $tanggal = CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $nilai));
            } catch (Throwable) {
                $tanggal = null;
            }
        } else {
            foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'd.m.Y', 'Y/m/d'] as $format) {
                try {
                    $coba = CarbonImmutable::createFromFormat('!'.$format, $teks);
                } catch (Throwable) {
                    continue;
                }

                if ($coba && $coba->format($format) === $teks) {
                    $tanggal = $coba;
                    break;
                }
            }
        }

        if (! $tanggal) {
            $galat[] = "{$label} tidak valid (gunakan format YYYY-MM-DD atau DD/MM/YYYY).";

            return null;
        }

        if ($tanggal->isFuture()) {
            $galat[] = "{$label} tidak boleh di masa depan.";

            return null;
        }

        return $tanggal->format('Y-m-d');
    }
}
