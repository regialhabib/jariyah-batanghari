<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $kecamatan_id
 * @property string $nama
 * @property string $jenis desa|kelurahan
 * @property string|null $kode
 * @property-read Kecamatan $kecamatan
 */
#[Fillable(['kecamatan_id', 'nama', 'jenis', 'kode'])]
class Desa extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Kecamatan, $this>
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    /**
     * @return HasMany<TanahWakaf, $this>
     */
    public function tanahWakafs(): HasMany
    {
        return $this->hasMany(TanahWakaf::class);
    }

    public function namaLengkap(): string
    {
        return ($this->jenis === 'kelurahan' ? 'Kel. ' : 'Desa ').$this->nama;
    }
}
