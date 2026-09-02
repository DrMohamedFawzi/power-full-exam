<?php

declare(strict_types=1);

namespace Tests\Feature\Overwatch;

use App\Modules\Identity\Models\User;
use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Models\Threat;
use App\Support\Enums\Severity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ThreatsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_threats_paginated_at_twenty(): void
    {
        $institution = User::factory()->institution()->create();
        Threat::factory()->count(25)->create();

        $response = $this->actingAs($institution)->get(route('overwatch.threats.index'));

        $response->assertOk();
        $response->assertViewHas('threats', fn ($threats) => $threats->count() === 20 && $threats->total() === 25);
    }

    public function test_filters_by_attack_type(): void
    {
        $institution = User::factory()->institution()->create();
        Threat::factory()->ofType(AttackType::SqlInjection)->create();
        Threat::factory()->ofType(AttackType::CrossSiteScripting)->create();

        $response = $this->actingAs($institution)->get(route('overwatch.threats.index', [
            'attack_type' => AttackType::SqlInjection->value,
        ]));

        $response->assertViewHas('threats', function ($threats) {
            return $threats->total() === 1
                && $threats->first()['attack_type_label'] === AttackType::SqlInjection->label();
        });
    }

    public function test_filters_by_severity_ip_and_date_range(): void
    {
        $institution = User::factory()->institution()->create();

        Threat::factory()->fromIp('203.0.113.20')->ofSeverity(Severity::Critical)->create(['detected_at' => now()]);
        Threat::factory()->fromIp('203.0.113.21')->ofSeverity(Severity::Low)->create(['detected_at' => now()->subDays(10)]);

        $response = $this->actingAs($institution)->get(route('overwatch.threats.index', [
            'ip_address' => '203.0.113.20',
            'severity' => Severity::Critical->value,
            'date_from' => now()->toDateString(),
        ]));

        $response->assertViewHas('threats', fn ($threats) => $threats->total() === 1);
    }

    public function test_payload_is_escaped_and_never_rendered_as_html(): void
    {
        $institution = User::factory()->institution()->create();
        Threat::factory()->create(['payload' => '<script>alert("xss")</script>']);

        $response = $this->actingAs($institution)->get(route('overwatch.threats.index'));

        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $institution = User::factory()->institution()->create();

        $response = $this->actingAs($institution)->get(route('overwatch.threats.index', [
            'attack_type' => 'not-a-real-type',
        ]));

        $response->assertSessionHasErrors('attack_type');
    }
}
