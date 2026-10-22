<?php

namespace Database\Factories;

use App\Models\Unduhan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unduhan>
 */
class UnduhanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => 'Formulir '.fake()->words(2, true),
            'keterangan' => 'Unduh di sini',
            'file' => null,
            'urutan' => fake()->numberBetween(1, 10),
        ];
    }
}
