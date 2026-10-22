<?php

namespace Database\Factories;

use App\Models\PanduanLangkah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanduanLangkah>
 */
class PanduanLangkahFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(3),
            'deskripsi' => fake()->sentence(12),
            'urutan' => fake()->numberBetween(1, 10),
        ];
    }
}
