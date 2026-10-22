<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function nazir(): static
    {
        return $this->state(fn (array $attributes) => [
            'nik' => fake()->unique()->numerify('1504############'),
            'phone' => fake()->numerify('08##########'),
        ])->afterCreating(fn (User $user) => $user->assignRole(Role::Nazir->value));
    }

    public function kua(?Kecamatan $kecamatan = null): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => fake()->unique()->userName(),
            'kecamatan_id' => $kecamatan?->id ?? Kecamatan::factory(),
        ])->afterCreating(fn (User $user) => $user->assignRole(Role::Kua->value));
    }

    public function bpn(): static
    {
        return $this->state(fn (array $attributes) => ['username' => fake()->unique()->userName()])
            ->afterCreating(fn (User $user) => $user->assignRole(Role::Bpn->value));
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['username' => fake()->unique()->userName()])
            ->afterCreating(fn (User $user) => $user->assignRole(Role::AdminBpn->value));
    }
}
