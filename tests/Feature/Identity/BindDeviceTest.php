<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Actions\BindDevice;
use App\Modules\Identity\Data\FingerprintPayload;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BindDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_binding_an_unseen_device_creates_it_and_reports_it_as_new(): void
    {
        $student = User::factory()->student()->create();

        $result = app(BindDevice::class)(
            $student,
            'device-hash-1',
            new FingerprintPayload(userAgent: 'Test-Agent', canvasHash: 'abc'),
            'حاسوبي',
            '10.0.0.1',
        );

        $this->assertTrue($result->isNewDevice);
        $this->assertSame('device-hash-1', $result->device->device_hash);
        $this->assertSame(1, $result->device->fingerprints()->count());
    }

    public function test_binding_an_already_known_device_is_reported_as_not_new(): void
    {
        $student = User::factory()->student()->create();
        $action = app(BindDevice::class);

        $action($student, 'device-hash-2', new FingerprintPayload, null, '10.0.0.1');
        $result = $action($student, 'device-hash-2', new FingerprintPayload, null, '10.0.0.2');

        $this->assertFalse($result->isNewDevice);
        $this->assertSame('10.0.0.2', $result->device->last_ip);
        $this->assertSame(2, $result->device->fingerprints()->count());
    }
}
