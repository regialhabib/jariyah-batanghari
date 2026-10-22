<?php

namespace App\Models;

use App\Enums\JenisPengajuan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property JenisPengajuan $jenis_pengajuan
 * @property string $nama
 * @property string|null $keterangan
 * @property bool $wajib
 * @property int $urutan
 */
#[Fillable(['jenis_pengajuan', 'nama', 'keterangan', 'wajib', 'urutan'])]
class Persyaratan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'jenis_pengajuan' => JenisPengajuan::class,
            'wajib' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * @param  Builder<Persyaratan>  $query
     */
    public function scopeUntuk(Builder $query, JenisPengajuan $jenis): void
    {
        $query->where('jenis_pengajuan', $jenis->value)->orderBy('urutan')->orderBy('id');
    }
}
