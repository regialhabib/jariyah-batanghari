<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Artikel Informasi Wakaf. Kolom `isi` berisi HTML yang sudah disanitasi saat disimpan.
 *
 * @property int $id
 * @property string $judul
 * @property string $slug
 * @property string $ikon
 * @property string $ringkasan
 * @property string $isi
 * @property int $urutan
 * @property bool $is_published
 */
#[Fillable(['judul', 'slug', 'ikon', 'ringkasan', 'isi', 'urutan', 'is_published'])]
class Artikel extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['judul', 'ringkasan', 'is_published'])->logOnlyDirty()->useLogName('konten');
    }

    /**
     * Setter yang membersihkan HTML dari editor sebelum disimpan.
     */
    public function setIsiAttribute(?string $value): void
    {
        $this->attributes['isi'] = clean($value ?? '');
    }

    /**
     * @param  Builder<Artikel>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('urutan')->orderBy('id');
    }
}
