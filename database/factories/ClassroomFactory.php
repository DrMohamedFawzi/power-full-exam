<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->lexify('??????')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'teacher_id' => User::factory()->teacher(),
            'institution_id' => null,
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['is_archived' => true]);
    }
}
