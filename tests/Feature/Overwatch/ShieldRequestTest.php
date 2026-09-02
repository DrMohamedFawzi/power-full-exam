<?php

declare(strict_types=1);

namespace Tests\Feature\Overwatch;

use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Http\Middleware\ShieldRequest;
use App\Modules\Overwatch\Models\BannedIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Exercises ShieldRequest directly against hand-built Request instances, so
 * these tests never depend on which routes other modules have registered.
 */
final class ShieldRequestTest extends TestCase
{
    use RefreshDatabase;

    private ShieldRequest $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new ShieldRequest;
        config(['aegis.overwatch.enabled' => true, 'aegis.overwatch.trusted_ips' => []]);
    }

    private function passthrough(Request $request): Response
    {
        return $this->middleware->handle($request, fn (Request $r): Response => response('ok'));
    }

    public function test_disabled_shield_lets_everything_through(): void
    {
        config(['aegis.overwatch.enabled' => false]);

        $request = Request::create('/anything?x=1', 'GET');
        $response = $this->passthrough($request);

        $this->assertSame('ok', $response->getContent());
    }

    public function test_trusted_ip_bypasses_all_checks(): void
    {
        config(['aegis.overwatch.trusted_ips' => ['203.0.113.9']]);

        $request = Request::create('/anything', 'GET', server: ['REMOTE_ADDR' => '203.0.113.9']);
        $request->query->set('q', '<script>alert(1)</script>');

        $response = $this->passthrough($request);

        $this->assertSame('ok', $response->getContent());
        $this->assertDatabaseCount('threats', 0);
    }

    public function test_banned_ip_is_rejected_with_arabic_403_page(): void
    {
        BannedIp::create(['ip_address' => '198.51.100.5', 'banned_until' => null]);

        $request = Request::create('/dashboard', 'GET', server: ['REMOTE_ADDR' => '198.51.100.5']);

        $response = $this->passthrough($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('محظور', $response->getContent());
    }

    public function test_ban_lookup_is_cached(): void
    {
        BannedIp::create(['ip_address' => '198.51.100.6', 'banned_until' => null]);

        $request = Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '198.51.100.6']);
        $this->passthrough($request);

        $this->assertTrue((bool) Cache::get('overwatch:banned:198.51.100.6'));
    }

    public function test_rate_limit_trips_and_logs_rate_abuse(): void
    {
        config(['aegis.overwatch.rate_limit_per_minute' => 2]);

        $ip = '198.51.100.7';
        $make = fn (): Request => Request::create('/x', 'GET', server: ['REMOTE_ADDR' => $ip]);

        $this->passthrough($make());
        $this->passthrough($make());
        $response = $this->passthrough($make());

        $this->assertSame(429, $response->getStatusCode());

        $this->assertDatabaseHas('threats', [
            'ip_address' => $ip,
            'attack_type' => AttackType::RateAbuse->value,
        ]);
    }

    public function test_waf_detects_sql_injection_in_query_string(): void
    {
        $ip = '198.51.100.8';
        $request = Request::create('/search', 'GET', server: ['REMOTE_ADDR' => $ip]);
        $request->query->set('q', "1' OR '1'='1");

        $this->passthrough($request);

        $this->assertDatabaseHas('threats', [
            'ip_address' => $ip,
            'attack_type' => AttackType::SqlInjection->value,
        ]);
    }

    public function test_waf_does_not_flag_legitimate_exam_answer_body(): void
    {
        $ip = '198.51.100.9';
        $request = Request::create('/exam/42/answer', 'POST', [
            'answer' => 'الإجابة الصحيحة هي أن 5 < 10 و "الماء يغلي عند 100 درجة" وهذا كود: `print("hi")`',
            'payload' => json_encode(['nested' => ['ok' => true, 'note' => 'لا يوجد شيء مريب هنا']]),
        ], server: ['REMOTE_ADDR' => $ip]);

        $this->passthrough($request);

        $this->assertDatabaseCount('threats', 0);
    }

    public function test_waf_still_flags_attack_in_query_string_on_exam_paths(): void
    {
        $ip = '198.51.100.10';
        $request = Request::create('/exam/42/answer', 'GET', server: ['REMOTE_ADDR' => $ip]);
        $request->query->set('redirect', '<script>alert(1)</script>');

        $this->passthrough($request);

        $this->assertDatabaseHas('threats', [
            'ip_address' => $ip,
            'attack_type' => AttackType::CrossSiteScripting->value,
        ]);
    }

    public function test_auto_bans_ip_after_threshold_detections(): void
    {
        config(['aegis.overwatch.auto_ban_threshold' => 3, 'aegis.overwatch.auto_ban_minutes' => 60]);

        $ip = '198.51.100.11';

        for ($i = 0; $i < 3; $i++) {
            $request = Request::create('/search', 'GET', server: ['REMOTE_ADDR' => $ip]);
            $request->query->set('q', '<script>alert(1)</script>');
            $this->passthrough($request);
        }

        $this->assertDatabaseHas('banned_ips', ['ip_address' => $ip]);

        // BanIp busts the cached lookup immediately, so the very next request
        // is rejected without waiting for the cache TTL to expire.
        $this->assertNull(Cache::get("overwatch:banned:{$ip}"));

        $response = $this->passthrough(Request::create('/search', 'GET', server: ['REMOTE_ADDR' => $ip]));
        $this->assertSame(403, $response->getStatusCode());
    }
}
