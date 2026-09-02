<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Models\DeviceFingerprint;
use App\Modules\Identity\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceFingerprint>
 */
class DeviceFingerprintFactory extends Factory
{
    protected $model = DeviceFingerprint::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_device_id' => UserDevice::factory(),
            'user_agent' => fake()->userAgent(),
            'screen_resolution' => fake()->randomElement(['1920x1080', '1366x768', '2560x1440']),
            'timezone' => fake()->timezone(),
            'canvas_hash' => fake()->sha256(),
            'webgl_vendor' => fake()->randomElement(['Google Inc.', 'Intel Inc.', 'NVIDIA Corporation']),
            'webgl_renderer' => fake()->words(3, true),
            'is_headless' => false,
            'raw' => ['generated_by' => 'factory'],
        ];
    }

    public function headless(): static
    {
        return $this->state(fn (): array => ['is_headless' => true]);
    }
}
