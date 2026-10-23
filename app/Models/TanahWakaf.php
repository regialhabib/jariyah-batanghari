<?php

namespace App\Models;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $kode
 * @property string $nama
 * @property int|null $nazir_id
 * @property string|null $nama_nazir
 * @property string $wakif_nama
 * @property string|null $wakif_nik
 * @property int $desa_id
 * @property string|null $alamat
 * @property string $luas_m2
 * @property JenisPenggunaan $jenis_penggunaan
 * @property StatusSertifikat $status_sertifikat
 * @property string|null $nomor_aiw
 * @property Carbon|null $tanggal_aiw
 * @property string|null $nomor_sertifikat
 * @property Carbon|null $tanggal_sertifikat
 * @property string $latitude
 * @property string $longitude
 * @property array<string, mixed>|null $batas_geojson
 * @property string|null $foto
 * @property bool $is_published
 * @property string $sumber pengajuan|impor|manual
 * @property-read User|null $nazir
 * @property-read Desa $desa
 */
#[Fillable([
    'kode', 'nama', 'nazir_id', 'nama_nazir', 'wakif_nama', 'wakif_nik', 'desa_id', 'alamat', 'luas_m2',
    'jenis_penggunaan', 'status_sertifikat', 'nomor_aiw', 'tanggal_aiw', 'nomor_sertifikat', 'tanggal_sertifikat',
    'latitude', 'longitude', 'batas_geojson', 'foto', 'is_published', 'sumber',
])]
#[Hidden(['wakif_nik'])]
class TanahWakaf extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'jenis_penggunaan' => JenisPenggunaan::class,
            'status_sertifikat' => StatusSertifikat::class,
            'tanggal_aiw' => 'date',
            'tanggal_sertifikat' => 'date',
            'batas_geojson' => 'array',
            'is_published' => 'boolean',
            'luas_m2' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TanahWakaf $tanah) {
            $tanah->kode ??= static::buatKode($tanah->desa_id);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['updated_at', 'created_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('tanah_wakaf');
    }

    /**
     * Kode unik: TW-{id kecamatan 2 digit}-{urut 4 digit}, mis. TW-03-0012.
     */
    public static function buatKode(int $desaId): string
    {
        $kecamatanId = Desa::query()->whereKey($desaId)->value('kecamatan_id') ?? 0;
        $prefix = sprintf('TW-%02d-', $kecamatanId);

        $terakhir = static::query()->where('kode', 'like', $prefix.'%')->max('kode');
        $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.sprintf('%04d', $urut);
    }

    /**
     * Tanah lain yang sudah memakai nomor AIW/sertifikat ini (tanpa beda huruf besar-kecil dan spasi di tepi).
     *
     * @param  'nomor_aiw'|'nomor_sertifikat'  $kolom
     */
    public static function pemakaiNomor(string $kolom, string $nomor, ?int $kecualiId = null): ?self
    {
        if (! in_array($kolom, ['nomor_aiw', 'nomor_sertifikat'], true)) {
            throw new InvalidArgumentException("Kolom nomor tidak dikenal: {$kolom}");
        }

        $nomor = mb_strtolower(trim($nomor));
        if ($nomor === '') {
            return null;
        }

        return static::query()
            ->whereRaw("LOWER(TRIM({$kolom})) = ?", [$nomor])
            ->when($kecualiId, fn (Builder $q) => $q->whereKeyNot($kecualiId))
            ->first(['id', 'kode', 'nama']);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function nazir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nazir_id');
    }

    /**
     * @return BelongsTo<Desa, $this>
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * @return HasMany<Pengajuan, $this>
     */
    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }

    /**
     * Data yang terlihat oleh pengguna tertentu:
     * Nazir hanya miliknya, KUA hanya kecamatannya, BPN semua.
     *
     * @param  Builder<TanahWakaf>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        if ($user->isNazir()) {
            $query->where('nazir_id', $user->id);
        } elseif ($user->isKua()) {
            $query->whereHas('desa', fn (Builder $q) => $q->where('kecamatan_id', $user->kecamatan_id));
        } elseif (! $user->isBpn()) {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * @param  Builder<TanahWakaf>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function namaNazirTampil(): ?string
    {
        return $this->nazir?->name ?? $this->nama_nazir;
    }

    public function fotoUrl(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    public function kecamatanId(): ?int
    {
        return $this->desa?->kecamatan_id;
    }

    /**
     * Data aman untuk halaman/API publik (tanpa NIK, HP, atau email).
     *
     * @return array<string, mixed>
     */
    public function dataPublik(): array
    {
        return [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'nazir' => $this->namaNazirTampil(),
            'desa' => $this->desa?->nama,
            'kecamatan' => $this->desa?->kecamatan?->nama,
            'luas_m2' => (float) $this->luas_m2,
            'jenis_penggunaan' => $this->jenis_penggunaan->value,
            'jenis_penggunaan_label' => $this->jenis_penggunaan->label(),
            'status' => $this->status_sertifikat->value,
            'status_label' => $this->status_sertifikat->label(),
            'warna' => $this->status_sertifikat->warnaPeta(),
            'foto' => $this->fotoUrl(),
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'batas' => $this->batas_geojson,
            'url' => route('peta.show', $this->kode),
        ];
    }
}
