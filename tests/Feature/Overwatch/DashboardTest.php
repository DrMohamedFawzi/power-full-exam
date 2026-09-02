<?php

declare(strict_types=1);

namespace Tests\Feature\Overwatch;

use App\Modules\Identity\Models\User;
use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Models\BannedIp;
use App\Modules\Overwatch\Models\Threat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_can_view_the_dashboard_with_kpis(): void
    {
        $institution = User::factory()->institution()->create();

        Threat::factory()->today()->ofType(AttackType::RateAbuse)->create();
        Threat::factory()->today()->ofType(AttackType::SqlInjection)->create();
        BannedIp::factory()->create();

        $response = $this->actingAs($institution)->get(route('overwatch.dashboard'));

        $response->assertOk();
        $response->assertViewHas('kpis', fn (array $kpis) => $kpis['threats_today'] === 2
            && $kpis['blocked_ips'] === 1
            && $kpis['requests_rejected_today'] === 1);
    }

    public function test_teacher_cannot_view_the_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('overwatch.dashboard'))->assertForbidden();
    }
}
