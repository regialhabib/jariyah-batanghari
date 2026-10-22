<?php

namespace App\Services;

use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Models\Kecamatan;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Angka statistik tanah wakaf untuk Beranda (publik), dashboard, dan laporan.
 * Tanpa $user = data publik (hanya yang dipublikasikan); dengan $user = sesuai cakupan user.
 */
class StatistikWakaf
{
    /**
     * @return array{total: int, sudah: int, belum: int, proses: int, kecamatan: int, desa: int, luas_m2: float}
     */
    public function ringkasan(?User $user = null): array
    {
        $perStatus = $this->query($user)
            ->selectRaw('status_sertifikat, count(*) as jumlah')
            ->groupBy('status_sertifikat')
            ->pluck('jumlah', 'status_sertifikat');

        $wilayah = $this->query($user)
            ->join('desas', 'desas.id', '=', 'tanah_wakafs.desa_id')
            ->selectRaw('count(distinct desas.kecamatan_id) as kecamatan, count(distinct desas.id) as desa, coalesce(sum(tanah_wakafs.luas_m2), 0) as luas')
            ->first();

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
     * Jumlah bidang per kecamatan, dipecah per status sertifikat.
     *
     * @return Collection<int, array{kecamatan: string, total: int, sudah: int, belum: int, proses: int}>
     */
    public function perKecamatan(?User $user = null): Collection
    {
        $baris = $this->query($user)
            ->join('desas', 'desas.id', '=', 'tanah_wakafs.desa_id')
            ->selectRaw('desas.kecamatan_id, tanah_wakafs.status_sertifikat, count(*) as jumlah')
            ->groupBy('desas.kecamatan_id', 'tanah_wakafs.status_sertifikat')
            ->toBase()
            ->get()
            ->groupBy('kecamatan_id');

        $kecamatans = Kecamatan::query()->orderBy('nama')
            ->when($user?->isKua(), fn (Builder $q) => $q->whereKey($user->kecamatan_id))
            ->get();

        return $kecamatans->map(function (Kecamatan $kecamatan) use ($baris) {
            $data = collect($baris->get($kecamatan->id, []))->pluck('jumlah', 'status_sertifikat');

            return [
                'kecamatan' => $kecamatan->nama,
                'total' => (int) $data->sum(),
                'sudah' => (int) ($data[StatusSertifikat::Sudah->value] ?? 0),
                'belum' => (int) ($data[StatusSertifikat::Belum->value] ?? 0),
                'proses' => (int) ($data[StatusSertifikat::Proses->value] ?? 0),
            ];
        })->values();
    }

    /**
     * Jumlah pengajuan per status dalam cakupan user.
     *
     * @return array<string, int>
     */
    public function pengajuanPerStatus(User $user): array
    {
        $data = Pengajuan::query()->forUser($user)
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return collect(StatusPengajuan::cases())
            ->mapWithKeys(fn (StatusPengajuan $s) => [$s->value => (int) ($data[$s->value] ?? 0)])
            ->all();
    }

    /**
     * @return Builder<TanahWakaf>
     */
    private function query(?User $user): Builder
    {
        return $user
            ? TanahWakaf::query()->forUser($user)
            : TanahWakaf::query()->published();
    }
}
