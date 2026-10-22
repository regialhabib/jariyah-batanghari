<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Dokumen penting / formulir yang bisa diunduh publik (disk `public`).
 *
 * @property int $id
 * @property string $judul
 * @property string|null $keterangan
 * @property string|null $file
 * @property int $urutan
 * @property int $jumlah_unduh
 */
#[Fillable(['judul', 'keterangan', 'file', 'urutan'])]
class Unduhan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'jumlah_unduh' => 'integer',
        ];
    }

    public function tersedia(): bool
    {
        return filled($this->file);
    }

    public function url(): string
    {
        return route('unduhan.download', $this);
    }
}
