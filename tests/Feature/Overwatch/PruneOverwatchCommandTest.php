<?php

declare(strict_types=1);

namespace Tests\Feature\Overwatch;

use App\Modules\Overwatch\Models\BannedIp;
use App\Modules\Overwatch\Models\Threat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PruneOverwatchCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_old_threats_and_expired_bans(): void
    {
        Threat::factory()->create(['detected_at' => now()->subDays(100)]);
        Threat::factory()->create(['detected_at' => now()->subDays(5)]);

        BannedIp::factory()->expired()->create();
        BannedIp::factory()->permanent()->create();

        $this->artisan('overwatch:prune', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseCount('threats', 1);
        $this->assertDatabaseCount('banned_ips', 1);
    }
}
