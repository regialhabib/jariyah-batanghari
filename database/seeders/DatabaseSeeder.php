<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            WilayahSeeder::class,
            PersyaratanSeeder::class,
            KontenSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                UserDemoSeeder::class,
                TanahWakafDemoSeeder::class,
            ]);
        }
    }
}
