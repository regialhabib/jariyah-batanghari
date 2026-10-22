<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Database\Seeder;

/**
 * 8 kecamatan Kabupaten Batang Hari beserta sebagian desa/kelurahan.
 *
 * CATATAN: daftar desa di sini adalah DATA CONTOH dan belum lengkap. Ganti dengan data
 * resmi Kemendagri sebelum produksi (lihat docs/08-kebutuhan-data.md).
 */
class WilayahSeeder extends Seeder
{
    /**
     * Titik tengah perkiraan tiap kecamatan, dipakai seeder data demo untuk menyebar koordinat.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    public const PUSAT = [
        'Bajubang' => [-1.84, 103.22],
        'Batin XXIV' => [-1.78, 102.93],
        'Maro Sebo Ilir' => [-1.55, 103.33],
        'Maro Sebo Ulu' => [-1.62, 103.05],
        'Mersam' => [-1.50, 102.96],
        'Muara Bulian' => [-1.70, 103.27],
        'Muara Tembesi' => [-1.73, 103.10],
        'Pemayung' => [-1.64, 103.42],
    ];

    /**
     * @var array<string, list<string>> nama desa; awalan "Kel. " berarti kelurahan.
     */
    private const DESA = [
        'Bajubang' => ['Kel. Bajubang', 'Penerokan', 'Petajen', 'Bungku', 'Ladang Peris', 'Mekar Jaya'],
        'Batin XXIV' => ['Muara Jangga', 'Jelutih', 'Aur Gading', 'Simpang Aur Gading', 'Paku Aji'],
        'Maro Sebo Ilir' => ['Terusan', 'Bulian Baru', 'Kehidupan Baru', 'Sungai Ruan Ilir'],
        'Maro Sebo Ulu' => ['Peninjauan', 'Kampung Baru', 'Sungai Ruan Ulu', 'Teluk Leban', 'Sungai Rengas'],
        'Mersam' => ['Kembang Paseban', 'Benteng Rendah', 'Rantau Gedang', 'Sungai Puar', 'Mersam'],
        'Muara Bulian' => ['Kel. Rengas Condong', 'Kel. Pasar Baru', 'Kel. Teratai', 'Kel. Sridadi', 'Rambahan', 'Kilangan', 'Sungai Baung', 'Pelayangan'],
        'Muara Tembesi' => ['Kel. Pasar Muara Tembesi', 'Pulau', 'Rantau Kapas Tuo', 'Ampelu', 'Kampung Baru Tembesi'],
        'Pemayung' => ['Kubu Kandang', 'Lubuk Ruso', 'Jembatan Mas', 'Selat', 'Teluk'],
    ];

    public function run(): void
    {
        foreach (self::DESA as $namaKecamatan => $desas) {
            $kecamatan = Kecamatan::query()->firstOrCreate(['nama' => $namaKecamatan]);

            foreach ($desas as $nama) {
                $kelurahan = str_starts_with($nama, 'Kel. ');

                Desa::query()->firstOrCreate(
                    ['kecamatan_id' => $kecamatan->id, 'nama' => $kelurahan ? substr($nama, 5) : $nama],
                    ['jenis' => $kelurahan ? 'kelurahan' : 'desa'],
                );
            }
        }
    }
}
