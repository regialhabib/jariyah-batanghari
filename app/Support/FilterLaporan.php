<?php

namespace App\Support;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Filter laporan yang dipakai bersama oleh halaman Laporan, ekspor Excel, dan ekspor PDF,
 * sehingga ketiganya selalu menghasilkan data yang sama.
 *
 * Cakupan user selalu diterapkan (TanahWakaf/Pengajuan::forUser); KUA dikunci ke kecamatannya
 * sendiri apa pun input kecamatan yang dikirim. Input mentah divalidasi di sini (nilai tak dikenal diabaikan).
 */
final class FilterLaporan
{
    /** Kunci query string yang dikenali. */
    public const KUNCI = ['kecamatan', 'status', 'jenis', 'dari', 'sampai'];

    private function __construct(
        public readonly User $user,
        public readonly ?int $kecamatanId,
        public readonly ?StatusSertifikat $status,
        public readonly ?JenisPenggunaan $jenis,
        public readonly ?CarbonImmutable $dari,
        public readonly ?CarbonImmutable $sampai,
    ) {}

    /**
     * @param  array<string, mixed>  $input  mis. $request->query() atau properti Livewire.
     */
    public static function dari(User $user, array $input): self
    {
        $kecamatanId = filter_var($input['kecamatan'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        if ($user->isKua()) {
            $kecamatanId = $user->kecamatan_id;
        } elseif ($kecamatanId && ! Kecamatan::query()->whereKey($kecamatanId)->exists()) {
            $kecamatanId = null;
        }

        $dari = self::tanggal($input['dari'] ?? null);
        $sampai = self::tanggal($input['sampai'] ?? null);
        if ($dari && $sampai && $dari->greaterThan($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return new self(
            $user,
            $kecamatanId,
            is_string($input['status'] ?? null) ? StatusSertifikat::tryFrom($input['status']) : null,
            is_string($input['jenis'] ?? null) ? JenisPenggunaan::tryFrom($input['jenis']) : null,
            $dari,
            $sampai,
        );
    }

    private static function tanggal(mixed $nilai): ?CarbonImmutable
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        try {
            $tanggal = CarbonImmutable::createFromFormat('!Y-m-d', $nilai);

            return $tanggal && $tanggal->format('Y-m-d') === $nilai ? $tanggal : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Tanah wakaf sesuai cakupan user + filter kecamatan, status sertifikat, jenis penggunaan.
     *
     * @return Builder<TanahWakaf>
     */
    public function tanahQuery(): Builder
    {
        return TanahWakaf::query()
            ->forUser($this->user)
            ->when($this->kecamatanId, fn (Builder $q) => $q->whereHas('desa', fn (Builder $d) => $d->where('kecamatan_id', $this->kecamatanId)))
            ->when($this->status, fn (Builder $q) => $q->where('status_sertifikat', $this->status->value))
            ->when($this->jenis, fn (Builder $q) => $q->where('jenis_penggunaan', $this->jenis->value));
    }

    /**
     * Pengajuan sesuai cakupan user + filter kecamatan & jenis penggunaan tanahnya + periode diajukan_at.
     *
     * @return Builder<Pengajuan>
     */
    public function pengajuanQuery(): Builder
    {
        return Pengajuan::query()
            ->forUser($this->user)
            ->when($this->kecamatanId, fn (Builder $q) => $q->whereHas('tanahWakaf.desa', fn (Builder $d) => $d->where('kecamatan_id', $this->kecamatanId)))
            ->when($this->jenis, fn (Builder $q) => $q->whereHas('tanahWakaf', fn (Builder $t) => $t->where('jenis_penggunaan', $this->jenis->value)))
            ->when($this->dari, fn (Builder $q) => $q->where('diajukan_at', '>=', $this->dari->startOfDay()))
            ->when($this->sampai, fn (Builder $q) => $q->where('diajukan_at', '<=', $this->sampai->endOfDay()));
    }

    /**
     * Data tanah untuk tabel/ekspor, urut kecamatan → desa → kode. Tanpa NIK wakif.
     *
     * @return Builder<TanahWakaf>
     */
    public function tanahTerurut(): Builder
    {
        return $this->tanahQuery()
            ->select('tanah_wakafs.*')
            ->join('desas', 'desas.id', '=', 'tanah_wakafs.desa_id')
            ->join('kecamatans', 'kecamatans.id', '=', 'desas.kecamatan_id')
            ->with(['desa.kecamatan', 'nazir:id,name'])
            ->orderBy('kecamatans.nama')->orderBy('desas.nama')->orderBy('tanah_wakafs.kode')->orderBy('tanah_wakafs.id');
    }

    /**
     * @return array{total: int, sudah: int, belum: int, proses: int, kecamatan: int, desa: int, luas_m2: float}
     */
    public function ringkasan(): array
    {
        $perStatus = $this->tanahQuery()
            ->selectRaw('status_sertifikat, count(*) as jumlah')
            ->groupBy('status_sertifikat')
            ->toBase()->pluck('jumlah', 'status_sertifikat');

        $wilayah = $this->tanahQuery()
            ->join('desas', 'desas.id', '=', 'tanah_wakafs.desa_id')
            ->selectRaw('count(distinct desas.kecamatan_id) as kecamatan, count(distinct desas.id) as desa, coalesce(sum(tanah_wakafs.luas_m2), 0) as luas')
            ->toBase()->first();

        return [
            'total' => (int) $perStatus->sum(),
            'sudah' => (int) ($perStatus[StatusSertifikat::Sudah->value] ?? 0),
            'belum' => (int) ($perStatus[StatusSertifikat::Belum->value] ?? 0),
            'proses' => (int) ($perStatus[StatusSertifikat::Proses->value] ?? 0),
            'kecamatan' => (int) ($wilayah->kecamatan ?? 0),
            'desa' => (int) ($wilayah->desa ?? 0),
            'luas_m2' => (float) ($wilayah->luas ?? 0),
        ];
    }

    /**
     * Rekap per kecamatan (format sama dengan StatistikWakaf::perKecamatan, ditambah luas).
     *
     * @return Collection<int, array{kecamatan: string, total: int, sudah: int, belum: int, proses: int, luas_m2: float}>
     */
    public function perKecamatan(): Collection
    {
        $baris = $this->tanahQuery()
            ->join('desas', 'desas.id', '=', 'tanah_wakafs.desa_id')
            ->selectRaw('desas.kecamatan_id, tanah_wakafs.status_sertifikat, count(*) as jumlah, coalesce(sum(tanah_wakafs.luas_m2), 0) as luas')
            ->groupBy('desas.kecamatan_id', 'tanah_wakafs.status_sertifikat')
            ->toBase()->get()
            ->groupBy('kecamatan_id');

        $kecamatanId = $this->kecamatanId ?? ($this->user->isKua() ? $this->user->kecamatan_id : null);

        return Kecamatan::query()->orderBy('nama')
            ->when($kecamatanId, fn (Builder $q) => $q->whereKey($kecamatanId))
            ->get(['id', 'nama'])
            ->map(function (Kecamatan $kecamatan) use ($baris) {
                $data = collect($baris->get($kecamatan->id, []));
                $jumlah = $data->pluck('jumlah', 'status_sertifikat');

                return [
                    'kecamatan' => $kecamatan->nama,
                    'total' => (int) $jumlah->sum(),
                    'sudah' => (int) ($jumlah[StatusSertifikat::Sudah->value] ?? 0),
                    'belum' => (int) ($jumlah[StatusSertifikat::Belum->value] ?? 0),
                    'proses' => (int) ($jumlah[StatusSertifikat::Proses->value] ?? 0),
                    'luas_m2' => (float) $data->sum('luas'),
                ];
            })->values();
    }

    /**
     * Jumlah pengajuan per status (semua status, termasuk nol).
     *
     * @return array<string, int>
     */
    public function rekapPengajuan(): array
    {
        $data = $this->pengajuanQuery()
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->toBase()->pluck('jumlah', 'status');

        return collect(StatusPengajuan::cases())
            ->mapWithKeys(fn (StatusPengajuan $s) => [$s->value => (int) ($data[$s->value] ?? 0)])
            ->all();
    }

    /**
     * Satu baris data tanah untuk tabel ekspor. Sengaja TIDAK memuat NIK wakif.
     *
     * @return array<string, string|float|null>
     */
    public static function baris(TanahWakaf $t): array
    {
        return [
            'kode' => $t->kode,
            'nama' => $t->nama,
            'kecamatan' => $t->desa->kecamatan->nama,
            'desa' => $t->desa->namaLengkap(),
            'alamat' => $t->alamat,
            'luas_m2' => (float) $t->luas_m2,
            'jenis_penggunaan' => $t->jenis_penggunaan->label(),
            'nazir' => $t->namaNazirTampil(),
            'wakif' => $t->wakif_nama,
            'status' => $t->status_sertifikat->label(),
            'nomor_aiw' => $t->nomor_aiw,
            'tanggal_aiw' => $t->tanggal_aiw?->format('d-m-Y'),
            'nomor_sertifikat' => $t->nomor_sertifikat,
            'tanggal_sertifikat' => $t->tanggal_sertifikat?->format('d-m-Y'),
            'koordinat' => $t->latitude.', '.$t->longitude,
        ];
    }

    /**
     * @return list<string>
     */
    public static function judulKolom(): array
    {
        return [
            'Kode', 'Nama Tanah Wakaf', 'Kecamatan', 'Desa/Kelurahan', 'Alamat', 'Luas (m²)', 'Jenis Penggunaan', 'Nazir',
            'Wakif', 'Status Sertifikat', 'Nomor AIW', 'Tanggal AIW', 'Nomor Sertifikat', 'Tanggal Sertifikat', 'Koordinat',
        ];
    }

    /**
     * Uraian filter untuk judul laporan, mis. ["Kecamatan: Muara Bulian", "Status: Sudah Bersertifikat"].
     *
     * @return list<string>
     */
    public function uraian(): array
    {
        $uraian = [
            'Kecamatan: '.($this->kecamatanId ? Kecamatan::query()->whereKey($this->kecamatanId)->value('nama') : 'Semua Kecamatan'),
            'Status Sertifikat: '.($this->status?->label() ?? 'Semua'),
            'Jenis Penggunaan: '.($this->jenis?->label() ?? 'Semua'),
        ];

        if ($this->dari || $this->sampai) {
            $uraian[] = 'Periode Pengajuan: '.($this->dari?->translatedFormat('d F Y') ?? 'awal').' s.d. '.($this->sampai?->translatedFormat('d F Y') ?? 'sekarang');
        }

        return $uraian;
    }

    /**
     * Filter sebagai query string untuk tautan ekspor.
     *
     * @return array<string, string>
     */
    public function keQuery(): array
    {
        return array_filter([
            'kecamatan' => $this->kecamatanId ? (string) $this->kecamatanId : null,
            'status' => $this->status?->value,
            'jenis' => $this->jenis?->value,
            'dari' => $this->dari?->format('Y-m-d'),
            'sampai' => $this->sampai?->format('Y-m-d'),
        ]);
    }

    public static function namaFile(string $ekstensi): string
    {
        return 'laporan-tanah-wakaf-'.now()->format('Ymd').'.'.$ekstensi;
    }
}
