<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $judul
 * @property string $deskripsi
 * @property int $urutan
 */
#[Fillable(['judul', 'deskripsi', 'urutan'])]
class PanduanLangkah extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }
}
