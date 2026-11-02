<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $pertanyaan
 * @property string $jawaban
 * @property int $urutan
 * @property bool $is_published
 */
class Faq extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['pertanyaan', 'jawaban', 'urutan', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * @param  Builder<Faq>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('urutan')->orderBy('id');
    }
}
