<?php

namespace Database\Factories;

use App\Enums\JenisPengajuan;
use App\Models\Persyaratan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persyaratan>
 */
class PersyaratanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis_pengajuan' => JenisPengajuan::Aiw,
            'nama' => 'Dokumen '.fake()->words(3, true),
            'wajib' => true,
            'urutan' => fake()->numberBetween(1, 20),
        ];
    }
}
