<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role & permission sesuai docs/02-peran-hak-akses.md.
 * Aturan berbasis data (cakupan kecamatan KUA, kepemilikan Nazir) ada di Policy.
 */
class RoleSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    public const PERMISSIONS = [
        'dashboard.admin' => ['admin_bpn', 'bpn', 'kua'],
        'tanah.lihat-semua' => ['admin_bpn', 'bpn', 'kua'],
        'tanah.kelola' => ['admin_bpn', 'bpn'],
        'tanah.impor' => ['admin_bpn', 'bpn'],
        'pengajuan.buat' => ['nazir'],
        'pengajuan.lihat-semua' => ['admin_bpn', 'bpn', 'kua'],
        'pengajuan.validasi-aiw' => ['kua'],
        'pengajuan.verifikasi-sertifikat' => ['admin_bpn', 'bpn'],
        'sertifikat.catat-terbit' => ['admin_bpn', 'bpn'],
        'laporan.lihat' => ['admin_bpn', 'bpn', 'kua'],
        'laporan.ekspor' => ['admin_bpn', 'bpn', 'kua'],
        'pengguna.kelola' => ['admin_bpn'],
        'konten.kelola' => ['admin_bpn'],
        'pesan.lihat' => ['admin_bpn', 'bpn'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        foreach (self::PERMISSIONS as $permission => $roles) {
            Permission::findOrCreate($permission, 'web');

            foreach ($roles as $role) {
                Role::findByName($role, 'web')->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
