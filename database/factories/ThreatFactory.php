<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Models\Threat;
use App\Support\Enums\Severity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Threat>
 */
class ThreatFactory extends Factory
{
    protected $model = Threat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attackType = fake()->randomElement(AttackType::cases());

        return [
            'ip_address' => fake()->ipv4(),
            'user_id' => null,
            'attack_type' => $attackType->value,
            'severity' => $attackType->severity()->value,
            'request_path' => '/'.fake()->slug(2),
            'payload' => fake()->sentence(),
            'user_agent' => fake()->userAgent(),
            'detected_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function ofType(AttackType $attackType): static
    {
        return $this->state(fn (): array => [
            'attack_type' => $attackType->value,
            'severity' => $attackType->severity()->value,
        ]);
    }

    public function ofSeverity(Severity $severity): static
    {
        return $this->state(fn (): array => ['severity' => $severity->value]);
    }

    public function fromIp(string $ip): static
    {
        return $this->state(fn (): array => ['ip_address' => $ip]);
    }

    public function today(): static
    {
        return $this->state(fn (): array => ['detected_at' => now()]);
    }
}
