<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Overwatch\Models\BannedIp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BannedIp>
 */
class BannedIpFactory extends Factory
{
    protected $model = BannedIp::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_address' => fake()->unique()->ipv4(),
            'reason' => fake()->sentence(),
            'banned_by' => null,
            'banned_until' => fake()->dateTimeBetween('+1 hour', '+30 days'),
        ];
    }

    public function permanent(): static
    {
        return $this->state(fn (): array => ['banned_until' => null]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['banned_until' => now()->subDay()]);
    }
}
