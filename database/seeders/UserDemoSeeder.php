<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Akun demo (password: "password"). Jangan dijalankan di produksi.
 * Lihat docs/09-pengujian.md untuk daftar akun.
 */
class UserDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->akun('Admin BPN', 'admin@jariyah.test', 'admin', Role::AdminBpn);
        $this->akun('Petugas BPN', 'bpn@jariyah.test', 'bpn', Role::Bpn);

        foreach (Kecamatan::query()->orderBy('nama')->get() as $kecamatan) {
            $slug = Str::slug($kecamatan->nama, '');
            $this->akun('KUA '.$kecamatan->nama, "kua.{$slug}@jariyah.test", "kua{$slug}", Role::Kua, ['kecamatan_id' => $kecamatan->id]);
        }

        $this->akun('Ahmad', 'nazir@jariyah.test', null, Role::Nazir, ['nik' => '1504010101800001', 'phone' => '081234567890']);
        $this->akun('Hasan Basri', 'nazir2@jariyah.test', null, Role::Nazir, ['nik' => '1504010101800002', 'phone' => '081234567891']);
    }

    /**
     * @param  array<string, mixed>  $tambahan
     */
    private function akun(string $nama, string $email, ?string $username, Role $role, array $tambahan = []): User
    {
        $user = User::query()->firstOrCreate(['email' => $email], [
            'name' => $nama,
            'username' => $username,
            'password' => 'password',
            'is_active' => true,
            ...$tambahan,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles([$role->value]);

        return $user;
    }
}
