<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Hak akses per role (docs/02-peran-hak-akses.md), didaftarkan sebagai Gate sehingga bisa dipakai
 * lewat `$user->can('laporan.ekspor')`, `@can(...)`, dan middleware `can:...`.
 * Aturan berbasis data (cakupan kecamatan KUA, kepemilikan Nazir) ada di Policy.
 */
class HakAkses
{
    /**
     * @var array<string, list<Role>>
     */
    public const DAFTAR = [
        'dashboard.admin' => [Role::AdminBpn, Role::Bpn, Role::Kua],
        'tanah.lihat-semua' => [Role::AdminBpn, Role::Bpn, Role::Kua],
        'tanah.kelola' => [Role::AdminBpn, Role::Bpn],
        'tanah.impor' => [Role::AdminBpn, Role::Bpn],
        'pengajuan.buat' => [Role::Nazir],
        'pengajuan.lihat-semua' => [Role::AdminBpn, Role::Bpn, Role::Kua],
        'pengajuan.validasi-aiw' => [Role::Kua],
        'pengajuan.verifikasi-sertifikat' => [Role::AdminBpn, Role::Bpn],
        'sertifikat.catat-terbit' => [Role::AdminBpn, Role::Bpn],
        'laporan.lihat' => [Role::AdminBpn, Role::Bpn, Role::Kua],
        'laporan.ekspor' => [Role::AdminBpn, Role::Bpn, Role::Kua],
        'pengguna.kelola' => [Role::AdminBpn],
        'konten.kelola' => [Role::AdminBpn],
        'pesan.lihat' => [Role::AdminBpn, Role::Bpn],
    ];

    public static function daftarkan(): void
    {
        foreach (self::DAFTAR as $hak => $roles) {
            Gate::define($hak, fn (User $user): bool => $user->hasRole(...$roles));
        }
    }
}
