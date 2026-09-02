<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Models\BannedIp;
use App\Modules\Overwatch\Models\Threat;
use Illuminate\Database\Seeder;

/**
 * Demo data for the threat console: a spread of threats over the last two
 * weeks across every attack type, plus a couple of active and expired bans.
 *
 * Not wired into DatabaseSeeder.php (owned by another module) — run directly:
 *   php artisan db:seed --class="Database\Seeders\OverwatchSeeder"
 */
final class OverwatchSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AttackType::cases() as $attackType) {
            Threat::factory()
                ->count(random_int(3, 8))
                ->ofType($attackType)
                ->create();
        }

        BannedIp::factory()->count(3)->create();
        BannedIp::factory()->permanent()->create();
        BannedIp::factory()->expired()->count(2)->create();
    }
}
