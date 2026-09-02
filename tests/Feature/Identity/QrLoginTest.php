<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Actions\IssueQrLoginToken;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_view_their_qr_login_code(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/qr-login/show');

        $response->assertOk();
        $response->assertSee('<svg', false);
    }

    public function test_scanning_a_valid_token_logs_the_user_in(): void
    {
        $student = User::factory()->student()->create();
        $token = app(IssueQrLoginToken::class)($student);

        $response = $this->get('/qr-login?token='.$token);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($student);
    }

    public function test_a_qr_token_can_only_be_used_once(): void
    {
        $student = User::factory()->student()->create();
        $token = app(IssueQrLoginToken::class)($student);

        $this->get('/qr-login?token='.$token);
        $this->post('/logout');

        $response = $this->get('/qr-login?token='.$token);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_invalid_token_redirects_to_login_with_an_error(): void
    {
        $response = $this->get('/qr-login?token=not-a-real-token');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
