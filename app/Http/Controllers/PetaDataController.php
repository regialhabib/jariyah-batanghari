<?php

namespace App\Http\Controllers;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\TanahWakaf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GeoJSON tanah wakaf publik untuk peta (docs/komponen-peta.md).
 *
 * Hanya tanah yang dipublikasikan; properti fitur = TanahWakaf::dataPublik() (tanpa NIK/HP/email).
 * Filter query: q (nama/kode/desa), kecamatan (id), jenis (JenisPenggunaan), status (StatusSertifikat).
 */
class PetaDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $filter = $request->only(['q', 'kecamatan', 'jenis', 'status']);

        $tanahs = static::query($filter)
            ->with(['desa.kecamatan', 'nazir'])
            ->orderBy('nama')
            ->get();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $tanahs->map(fn (TanahWakaf $tanah) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $tanah->longitude, (float) $tanah->latitude],
                ],
                'properties' => $tanah->dataPublik(),
            ])->values(),
            'ringkasan' => [
                'total' => $tanahs->count(),
                'kecamatan' => $tanahs->pluck('desa.kecamatan_id')->filter()->unique()->count(),
                'desa' => $tanahs->pluck('desa_id')->unique()->count(),
            ],
        ]);
    }

    /**
     * Query tanah wakaf publik dengan filter peta. Dipakai juga oleh halaman publik Peta Wakaf
     * agar ringkasan di panel "Informasi" selalu sama dengan data di peta.
     *
     * @param  array<string, mixed>  $filter  q, kecamatan, jenis, status (nilai tak valid diabaikan)
     * @return Builder<TanahWakaf>
     */
    public static function query(array $filter = []): Builder
    {
        $q = trim(mb_substr((string) ($filter['q'] ?? ''), 0, 100));
        $kecamatan = filter_var($filter['kecamatan'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $jenis = is_string($filter['jenis'] ?? null) ? JenisPenggunaan::tryFrom($filter['jenis']) : null;
        $status = is_string($filter['status'] ?? null) ? StatusSertifikat::tryFrom($filter['status']) : null;

        return TanahWakaf::query()
            ->published()
            ->when($q !== '', function (Builder $query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';

                $query->where(fn (Builder $w) => $w
                    ->where('nama', 'like', $like)
                    ->orWhere('kode', 'like', $like)
                    ->orWhereHas('desa', fn (Builder $d) => $d->where('nama', 'like', $like)));
            })
            ->when($kecamatan, fn (Builder $query) => $query->whereHas('desa', fn (Builder $d) => $d->where('kecamatan_id', $kecamatan)))
            ->when($jenis, fn (Builder $query) => $query->where('jenis_penggunaan', $jenis))
            ->when($status, fn (Builder $query) => $query->where('status_sertifikat', $status));
    }

    /**
     * Ringkasan sebaran untuk filter tertentu.
     *
     * @param  array<string, mixed>  $filter
     * @return array{total: int, kecamatan: int, desa: int}
     */
    public static function ringkasan(array $filter = []): array
    {
        $desaIds = static::query($filter)->select('desa_id');

        return [
            'total' => static::query($filter)->count(),
            'kecamatan' => Desa::query()->whereIn('id', $desaIds)->distinct()->count('kecamatan_id'),
            'desa' => static::query($filter)->distinct()->count('desa_id'),
        ];
    }
}
