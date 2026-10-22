<?php

namespace App\Models;

use App\Enums\JenisPengajuan;
use App\Enums\KategoriDokumen;
use App\Enums\StatusPengajuan;
use App\Enums\StatusValidasiDokumen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $nomor
 * @property JenisPengajuan $jenis
 * @property int $tanah_wakaf_id
 * @property int $nazir_id
 * @property StatusPengajuan $status
 * @property Carbon|null $diajukan_at
 * @property Carbon|null $selesai_at
 * @property int|null $pemeriksa_id
 * @property string|null $catatan_pemeriksa
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read TanahWakaf $tanahWakaf
 * @property-read User $nazir
 * @property-read User|null $pemeriksa
 * @property-read Collection<int, PengajuanDokumen> $dokumens
 * @property-read Collection<int, RiwayatPengajuan> $riwayats
 */
#[Fillable(['jenis', 'tanah_wakaf_id', 'nazir_id', 'status'])]
class Pengajuan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'jenis' => JenisPengajuan::class,
            'status' => StatusPengajuan::class,
            'diajukan_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    /**
     * Nomor pengajuan: JRY-{tahun}-{urut 4 digit}.
     */
    public static function buatNomor(): string
    {
        $prefix = 'JRY-'.now()->year.'-';
        $terakhir = static::query()->where('nomor', 'like', $prefix.'%')->max('nomor');
        $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.sprintf('%04d', $urut);
    }

    /**
     * @return BelongsTo<TanahWakaf, $this>
     */
    public function tanahWakaf(): BelongsTo
    {
        return $this->belongsTo(TanahWakaf::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function nazir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nazir_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemeriksa_id');
    }

    /**
     * @return HasMany<PengajuanDokumen, $this>
     */
    public function dokumens(): HasMany
    {
        return $this->hasMany(PengajuanDokumen::class);
    }

    /**
     * @return HasMany<RiwayatPengajuan, $this>
     */
    public function riwayats(): HasMany
    {
        return $this->hasMany(RiwayatPengajuan::class)->latest('id');
    }

    /**
     * @param  Builder<Pengajuan>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        if ($user->isNazir()) {
            $query->where('nazir_id', $user->id);
        } elseif ($user->isKua()) {
            $query->whereHas('tanahWakaf.desa', fn (Builder $q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif (! $user->isBpn()) {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Persyaratan untuk jenis pengajuan ini.
     *
     * @return Collection<int, Persyaratan>
     */
    public function persyaratans(): Collection
    {
        return Persyaratan::query()->untuk($this->jenis)->get();
    }

    /**
     * Persyaratan wajib yang belum diunggah.
     *
     * @return Collection<int, Persyaratan>
     */
    public function persyaratanBelumLengkap(): Collection
    {
        $terunggah = $this->dokumens()
            ->where('kategori', KategoriDokumen::Persyaratan->value)
            ->pluck('persyaratan_id')
            ->all();

        return $this->persyaratans()
            ->filter(fn (Persyaratan $p) => $p->wajib && ! in_array($p->id, $terunggah, true))
            ->values();
    }

    public function adaDokumenTidakValid(): bool
    {
        return $this->dokumens()
            ->where('status_validasi', StatusValidasiDokumen::TidakValid->value)
            ->exists();
    }
}
