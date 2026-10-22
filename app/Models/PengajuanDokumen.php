<?php

namespace App\Models;

use App\Enums\KategoriDokumen;
use App\Enums\StatusValidasiDokumen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pengajuan_id
 * @property int|null $persyaratan_id
 * @property KategoriDokumen $kategori
 * @property string $path
 * @property string $nama_asli
 * @property string|null $mime
 * @property int $ukuran
 * @property StatusValidasiDokumen $status_validasi
 * @property string|null $catatan
 * @property-read Pengajuan $pengajuan
 * @property-read Persyaratan|null $persyaratan
 */
#[Fillable(['pengajuan_id', 'persyaratan_id', 'kategori', 'path', 'nama_asli', 'mime', 'ukuran', 'status_validasi', 'catatan'])]
class PengajuanDokumen extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kategori' => KategoriDokumen::class,
            'status_validasi' => StatusValidasiDokumen::class,
            'ukuran' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Pengajuan, $this>
     */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /**
     * @return BelongsTo<Persyaratan, $this>
     */
    public function persyaratan(): BelongsTo
    {
        return $this->belongsTo(Persyaratan::class);
    }

    public function judul(): string
    {
        return $this->persyaratan?->nama ?? $this->kategori->label();
    }

    public function url(): string
    {
        return route('dokumen.show', $this);
    }
}
