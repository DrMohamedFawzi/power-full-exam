<?php

declare(strict_types=1);

namespace Tests\Unit\Overwatch;

use App\Modules\Overwatch\Actions\BanIp;
use App\Modules\Overwatch\Actions\RecordFailedLogin;
use App\Modules\Overwatch\Actions\RecordThreat;
use App\Modules\Overwatch\Actions\UnbanIp;
use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Models\BannedIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class ActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ban_ip_creates_a_ban_and_busts_the_cache(): void
    {
        Cache::put('overwatch:banned:192.0.2.1', false);

        $ban = app(BanIp::class)('192.0.2.1', 'test reason', 30);

        $this->assertDatabaseHas('banned_ips', ['ip_address' => '192.0.2.1', 'reason' => 'test reason']);
        $this->assertNotNull($ban->banned_until);
        $this->assertNull(Cache::get('overwatch:banned:192.0.2.1'));
    }

    public function test_ban_ip_without_minutes_is_permanent(): void
    {
        $ban = app(BanIp::class)('192.0.2.2', 'permanent');

        $this->assertNull($ban->banned_until);
    }

    public function test_unban_ip_deletes_the_row_and_busts_the_cache(): void
    {
        $ban = BannedIp::create(['ip_address' => '192.0.2.3', 'banned_until' => null]);
        Cache::put('overwatch:banned:192.0.2.3', true);

        app(UnbanIp::class)($ban);

        $this->assertDatabaseMissing('banned_ips', ['ip_address' => '192.0.2.3']);
        $this->assertNull(Cache::get('overwatch:banned:192.0.2.3'));
    }

    public function test_record_threat_auto_bans_after_threshold(): void
    {
        config(['aegis.overwatch.auto_ban_threshold' => 2, 'aegis.overwatch.auto_ban_minutes' => 15]);

        $record = app(RecordThreat::class);
        $record('192.0.2.4', AttackType::SqlInjection);
        $record('192.0.2.4', AttackType::SqlInjection);

        $this->assertDatabaseHas('banned_ips', ['ip_address' => '192.0.2.4']);
    }

    public function test_record_failed_login_logs_brute_force_after_threshold(): void
    {
        for ($i = 0; $i < 4; $i++) {
            app(RecordFailedLogin::class)('192.0.2.5', 'someone@example.com');
        }

        $this->assertDatabaseCount('threats', 0);

        app(RecordFailedLogin::class)('192.0.2.5', 'someone@example.com');

        $this->assertDatabaseHas('threats', [
            'ip_address' => '192.0.2.5',
            'attack_type' => AttackType::BruteForce->value,
        ]);
    }
}
