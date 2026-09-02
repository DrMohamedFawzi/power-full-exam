<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->lexify('INST-????'),
            'contact_email' => fake()->unique()->companyEmail(),
            'logo_path' => null,
        ];
    }
}
