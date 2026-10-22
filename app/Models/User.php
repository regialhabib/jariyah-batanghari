<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property Role $role
 * @property string|null $nik
 * @property string $email
 * @property string|null $phone
 * @property int|null $kecamatan_id
 * @property bool $is_active
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Kecamatan|null $kecamatan
 */
#[Fillable(['name', 'username', 'nik', 'email', 'phone', 'kecamatan_id', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token', 'nik'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'nazir',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => Role::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'role', 'email', 'phone', 'kecamatan_id', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('pengguna');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * @return BelongsTo<Kecamatan, $this>
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    /**
     * @return HasMany<TanahWakaf, $this>
     */
    public function tanahWakafs(): HasMany
    {
        return $this->hasMany(TanahWakaf::class, 'nazir_id');
    }

    /**
     * @return HasMany<Pengajuan, $this>
     */
    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'nazir_id');
    }

    /**
     * Role pengguna. Setiap akun hanya punya satu role, disimpan di kolom `users.role`.
     */
    public function peran(): Role
    {
        return $this->role;
    }

    public function hasRole(Role|string ...$roles): bool
    {
        return in_array($this->role, array_map(fn (Role|string $r) => $r instanceof Role ? $r : Role::from($r), $roles), true);
    }

    public function isNazir(): bool
    {
        return $this->role === Role::Nazir;
    }

    public function isKua(): bool
    {
        return $this->role === Role::Kua;
    }

    public function isBpn(): bool
    {
        return $this->hasRole(...Role::bpn());
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::AdminBpn;
    }

    public function isPetugas(): bool
    {
        return $this->hasRole(...Role::petugas());
    }

    /**
     * @param  Role|string|array<int, Role|string>  $roles
     */
    public function scopeRole(Builder $query, Role|string|array $roles): void
    {
        $query->whereIn('role', array_map(fn (Role|string $r) => $r instanceof Role ? $r->value : $r, (array) $roles));
    }

    /**
     * URL dashboard sesuai role.
     */
    public function dashboardUrl(): string
    {
        return $this->isNazir() ? route('nazir.dashboard') : route('admin.dashboard');
    }
}
