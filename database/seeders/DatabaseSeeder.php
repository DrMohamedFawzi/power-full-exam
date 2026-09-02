<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Demo data, seeded in module dependency order so each context can rely on the
 * ones below it already existing.
 *
 * Each module owns its own seeder; this file only decides the order.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            IdentitySeeder::class,
            AcademicsSeeder::class,
            AssessmentSeeder::class,
            ProctoringSeeder::class,
            OverwatchSeeder::class,
        ]);
    }
}
