<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Enums\DeviceStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_list_their_trusted_devices(): void
    {
        $student = User::factory()->student()->create();
        UserDevice::factory()->for($student)->create(['label' => 'حاسوبي المحمول']);

        $response = $this->actingAs($student)->get('/student/devices');

        $response->assertOk();
        $response->assertSee('حاسوبي المحمول');
    }

    public function test_a_student_can_revoke_their_own_device(): void
    {
        $student = User::factory()->student()->create();
        $device = UserDevice::factory()->for($student)->create();

        $response = $this->actingAs($student)->delete("/student/devices/{$device->id}");

        $response->assertRedirect(route('student.devices.index'));
        $this->assertSame(DeviceStatus::Revoked, $device->fresh()->status);
    }

    public function test_a_student_cannot_revoke_another_students_device(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $device = UserDevice::factory()->for($otherStudent)->create();

        $response = $this->actingAs($student)->delete("/student/devices/{$device->id}");

        $response->assertForbidden();
        $this->assertSame(DeviceStatus::Active, $device->fresh()->status);
    }

    public function test_a_teacher_cannot_access_the_student_devices_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get('/student/devices');

        $response->assertForbidden();
    }
}
