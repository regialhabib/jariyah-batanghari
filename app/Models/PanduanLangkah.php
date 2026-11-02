<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $judul
 * @property string $deskripsi
 * @property int $urutan
 */
class PanduanLangkah extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['judul', 'deskripsi', 'urutan'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }
}
