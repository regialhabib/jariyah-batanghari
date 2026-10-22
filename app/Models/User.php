<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
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
    use HasFactory, HasRoles, LogsActivity, Notifiable;

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
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'email', 'phone', 'kecamatan_id', 'is_active'])
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
     * Role utama pengguna (setiap akun hanya punya satu role).
     */
    public function peran(): ?Role
    {
        $nama = $this->getRoleNames()->first();

        return $nama ? Role::tryFrom($nama) : null;
    }

    public function isNazir(): bool
    {
        return $this->hasRole(Role::Nazir->value);
    }

    public function isKua(): bool
    {
        return $this->hasRole(Role::Kua->value);
    }

    public function isBpn(): bool
    {
        return $this->hasAnyRole(array_map(fn (Role $r) => $r->value, Role::bpn()));
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::AdminBpn->value);
    }

    public function isPetugas(): bool
    {
        return $this->hasAnyRole(array_map(fn (Role $r) => $r->value, Role::petugas()));
    }

    /**
     * URL dashboard sesuai role.
     */
    public function dashboardUrl(): string
    {
        return $this->isNazir() ? route('nazir.dashboard') : route('admin.dashboard');
    }
}
