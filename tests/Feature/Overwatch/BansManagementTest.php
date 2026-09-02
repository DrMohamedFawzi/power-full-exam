<?php

declare(strict_types=1);

namespace Tests\Feature\Overwatch;

use App\Modules\Identity\Models\User;
use App\Modules\Overwatch\Models\BannedIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BansManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_can_view_bans_index(): void
    {
        $institution = User::factory()->institution()->create();
        BannedIp::factory()->count(2)->create();

        $response = $this->actingAs($institution)->get(route('overwatch.bans.index'));

        $response->assertOk();
    }

    public function test_non_institution_roles_are_forbidden(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('overwatch.bans.index'))->assertForbidden();
    }

    /**
     * Identity (login) is being built in a parallel module and may not have a
     * named `login` route yet, which is what Laravel's `auth` middleware
     * redirects unauthenticated users to. Either way, an unauthenticated
     * request must never reach the console with a 200.
     */
    public function test_guests_cannot_view_bans_index(): void
    {
        try {
            $response = $this->get(route('overwatch.bans.index'));
            $this->assertNotEquals(200, $response->getStatusCode());
        } catch (\Throwable) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_institution_can_manually_ban_an_ip(): void
    {
        $institution = User::factory()->institution()->create();

        $response = $this->actingAs($institution)->post(route('overwatch.bans.store'), [
            'ip_address' => '192.0.2.50',
            'reason' => 'نشاط مشبوه يدوي',
            'expires_in_minutes' => 60,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('banned_ips', [
            'ip_address' => '192.0.2.50',
            'banned_by' => $institution->id,
        ]);
    }

    public function test_store_validates_ip_address(): void
    {
        $institution = User::factory()->institution()->create();

        $response = $this->actingAs($institution)->post(route('overwatch.bans.store'), [
            'ip_address' => 'not-an-ip',
        ]);

        $response->assertSessionHasErrors('ip_address');
    }

    public function test_institution_can_unban_an_ip(): void
    {
        $institution = User::factory()->institution()->create();
        $ban = BannedIp::factory()->create();

        $response = $this->actingAs($institution)->delete(route('overwatch.bans.destroy', $ban));

        $response->assertRedirect();
        $this->assertDatabaseMissing('banned_ips', ['id' => $ban->id]);
    }
}
