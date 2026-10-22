<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Pesan dari form Kontak publik.
 *
 * @property int $id
 * @property string $nama
 * @property string $kontak
 * @property string $isi
 * @property string|null $ip
 * @property Carbon|null $dibaca_at
 * @property Carbon $created_at
 */
#[Fillable(['nama', 'kontak', 'isi', 'ip'])]
class Pesan extends Model
{
    protected function casts(): array
    {
        return ['dibaca_at' => 'datetime'];
    }
}
