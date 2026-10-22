<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pengajuan_id
 * @property int|null $user_id
 * @property StatusPengajuan|null $dari_status
 * @property StatusPengajuan $ke_status
 * @property string|null $catatan
 * @property Carbon $created_at
 * @property-read User|null $user
 */
#[Fillable(['pengajuan_id', 'user_id', 'dari_status', 'ke_status', 'catatan'])]
class RiwayatPengajuan extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'dari_status' => StatusPengajuan::class,
            'ke_status' => StatusPengajuan::class,
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
