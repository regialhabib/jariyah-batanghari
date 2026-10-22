<?php

namespace Database\Factories;

use App\Models\Artikel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Artikel>
 */
class ArtikelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->unique()->sentence(3);

        return [
            'judul' => $judul,
            'slug' => Str::slug($judul),
            'ikon' => 'book-open',
            'ringkasan' => fake()->sentence(12),
            'isi' => '<p>'.fake()->paragraph().'</p>',
            'urutan' => fake()->numberBetween(1, 20),
            'is_published' => true,
        ];
    }
}
