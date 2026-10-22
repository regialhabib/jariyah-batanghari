<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pertanyaan' => rtrim(fake()->sentence(), '.').'?',
            'jawaban' => fake()->paragraph(),
            'urutan' => fake()->numberBetween(1, 20),
            'is_published' => true,
        ];
    }
}
