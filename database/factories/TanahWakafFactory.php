<?php

namespace Database\Factories;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TanahWakaf>
 */
class TanahWakafFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(JenisPenggunaan::cases());

        return [
            'nama' => $jenis->label().' '.fake()->randomElement(['Al-Ikhlas', 'Nurul Huda', 'Al-Falah', 'Baitul Makmur', 'Nurul Iman', 'At-Taqwa', 'Al-Hidayah', 'Darussalam']),
            'nazir_id' => null,
            'nama_nazir' => fake()->name('male'),
            'wakif_nama' => fake()->name(),
            'wakif_nik' => fake()->numerify('1504############'),
            'desa_id' => Desa::factory(),
            'alamat' => fake()->streetAddress(),
            'luas_m2' => fake()->numberBetween(200, 5000),
            'jenis_penggunaan' => $jenis,
            'status_sertifikat' => StatusSertifikat::Belum,
            'nomor_aiw' => 'W.2/'.fake()->numberBetween(1, 300).'/'.fake()->year(),
            'tanggal_aiw' => fake()->dateTimeBetween('-10 years', '-1 month'),
            'latitude' => fake()->latitude(-1.95, -1.45),
            'longitude' => fake()->longitude(102.95, 103.50),
            'is_published' => true,
            'sumber' => 'manual',
        ];
    }

    public function milik(User $nazir): static
    {
        return $this->state(fn () => ['nazir_id' => $nazir->id, 'nama_nazir' => null]);
    }

    /**
     * Tanah baru dari pengajuan AIW yang belum disahkan.
     */
    public function tanpaAiw(): static
    {
        return $this->state(fn () => ['nomor_aiw' => null, 'tanggal_aiw' => null, 'is_published' => false, 'sumber' => 'pengajuan']);
    }

    public function bersertifikat(): static
    {
        return $this->state(fn () => [
            'status_sertifikat' => StatusSertifikat::Sudah,
            'nomor_sertifikat' => '10.04.'.fake()->numerify('##.##.#.#####'),
            'tanggal_sertifikat' => fake()->dateTimeBetween('-5 years', 'now'),
        ]);
    }
}
