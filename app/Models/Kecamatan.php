<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $nama
 * @property string|null $kode
 */
#[Fillable(['nama', 'kode'])]
class Kecamatan extends Model
{
    use HasFactory;

    /**
     * @return HasMany<Desa, $this>
     */
    public function desas(): HasMany
    {
        return $this->hasMany(Desa::class);
    }

    /**
     * @return HasManyThrough<TanahWakaf, Desa, $this>
     */
    public function tanahWakafs(): HasManyThrough
    {
        return $this->hasManyThrough(TanahWakaf::class, Desa::class);
    }
}
