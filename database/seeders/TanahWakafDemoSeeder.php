<?php

namespace Database\Seeders;

use App\Enums\JenisPengajuan;
use App\Enums\JenisPenggunaan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Pengajuan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Data demo: 125 bidang (90 bersertifikat, 35 belum) seperti angka di mockup Beranda,
 * plus 3 tanah milik Nazir demo "Ahmad" seperti di mockup dashboard Nazir.
 */
class TanahWakafDemoSeeder extends Seeder
{
    private const NAMA = ['Al-Ikhlas', 'Nurul Huda', 'Al-Falah', 'Baitul Makmur', 'Nurul Iman', 'At-Taqwa', 'Al-Hidayah', 'Darussalam', 'Al-Muhajirin', 'Baiturrahman', 'Al-Amin', 'Nurul Jannah'];

    public function run(): void
    {
        if (TanahWakaf::query()->exists()) {
            return;
        }

        $desas = Desa::query()->with('kecamatan')->get();
        $ahmad = User::query()->where('email', 'nazir@jariyah.test')->first();

        $this->tanahAhmad($ahmad, $desas);

        // 3 milik Ahmad: 2 bersertifikat, 1 dalam proses. Sisanya diatur agar total 90 sudah / 35 belum.
        for ($i = 0; $i < 122; $i++) {
            $desa = $desas->random();
            $jenis = fake()->randomElement([
                JenisPenggunaan::Masjid, JenisPenggunaan::Masjid, JenisPenggunaan::Mushola, JenisPenggunaan::Mushola,
                JenisPenggunaan::Pesantren, JenisPenggunaan::Sekolah, JenisPenggunaan::Makam, JenisPenggunaan::Lainnya,
            ]);
            $sudah = $i < 88;

            TanahWakaf::factory()
                ->when($sudah, fn ($f) => $f->bersertifikat())
                ->create([
                    'nama' => $jenis->label().' '.fake()->randomElement(self::NAMA),
                    'desa_id' => $desa->id,
                    'jenis_penggunaan' => $jenis,
                    'status_sertifikat' => $sudah ? StatusSertifikat::Sudah : StatusSertifikat::Belum,
                    ...$this->koordinat($desa),
                    'sumber' => 'impor',
                ]);
        }
    }

    /**
     * @param  Collection<int, Desa>  $desas
     */
    private function tanahAhmad(?User $ahmad, $desas): void
    {
        if (! $ahmad) {
            return;
        }

        $data = [
            ['Masjid Al-Ikhlas', 'Peninjauan', JenisPenggunaan::Masjid, 1500, StatusPengajuan::SertifikatTerbit],
            ['Mushola Nurul Huda', 'Sungai Rengas', JenisPenggunaan::Mushola, 450, StatusPengajuan::Diajukan],
            ['Pesantren Nurul Iman', 'Bajubang', JenisPenggunaan::Pesantren, 3200, StatusPengajuan::SertifikatTerbit],
        ];

        foreach ($data as [$nama, $namaDesa, $jenis, $luas, $status]) {
            $desa = $desas->firstWhere('nama', $namaDesa) ?? $desas->first();
            $terbit = $status === StatusPengajuan::SertifikatTerbit;

            $tanah = TanahWakaf::factory()->milik($ahmad)
                ->when($terbit, fn ($f) => $f->bersertifikat())
                ->create([
                    'nama' => $nama,
                    'desa_id' => $desa->id,
                    'jenis_penggunaan' => $jenis,
                    'luas_m2' => $luas,
                    'status_sertifikat' => $terbit ? StatusSertifikat::Sudah : StatusSertifikat::Proses,
                    ...$this->koordinat($desa),
                ]);

            $pengajuan = new Pengajuan([
                'jenis' => JenisPengajuan::SertifikatBelum,
                'tanah_wakaf_id' => $tanah->id,
                'nazir_id' => $ahmad->id,
                'status' => $status,
            ]);
            $pengajuan->nomor = Pengajuan::buatNomor();
            $pengajuan->diajukan_at = now()->subDays(fake()->numberBetween(10, 90));
            $pengajuan->selesai_at = $terbit ? now()->subDays(fake()->numberBetween(1, 9)) : null;
            $pengajuan->save();

            $pengajuan->riwayats()->create([
                'user_id' => $ahmad->id,
                'dari_status' => StatusPengajuan::Draf,
                'ke_status' => StatusPengajuan::Diajukan,
            ]);
        }
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    private function koordinat(Desa $desa): array
    {
        [$lat, $lng] = WilayahSeeder::PUSAT[$desa->kecamatan->nama] ?? [-1.70, 103.27];

        return [
            'latitude' => round($lat + fake()->randomFloat(4, -0.06, 0.06), 7),
            'longitude' => round($lng + fake()->randomFloat(4, -0.06, 0.06), 7),
        ];
    }
}
