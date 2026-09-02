<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_register_without_an_institution(): void
    {
        $response = $this->post('/register', [
            'role' => 'student',
            'username' => 'student_one',
            'email' => 'student@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'official_name' => 'طالب تجريبي',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        $user = User::where('email', 'student@example.com')->firstOrFail();
        $this->assertSame('student', $user->role->value);
        $this->assertTrue($user->is_approved);
        $this->assertNull($user->institution_id);
        $this->assertNotEmpty($user->qr_token);
    }

    public function test_a_teacher_is_created_unapproved_and_sent_to_the_pending_page(): void
    {
        $institution = Institution::factory()->create();

        $response = $this->post('/register', [
            'role' => 'teacher',
            'username' => 'teacher_one',
            'email' => 'teacher@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'official_name' => 'معلّم تجريبي',
            'institution_id' => $institution->id,
        ]);

        $response->assertRedirect(route('teacher.pending'));

        $user = User::where('email', 'teacher@example.com')->firstOrFail();
        $this->assertFalse($user->is_approved);
        $this->assertSame($institution->id, $user->institution_id);
    }

    public function test_a_teacher_registration_requires_an_institution(): void
    {
        $response = $this->post('/register', [
            'role' => 'teacher',
            'username' => 'teacher_two',
            'email' => 'teacher2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'official_name' => 'معلّم بدون مؤسسة',
        ]);

        $response->assertSessionHasErrors('institution_id');
        $this->assertGuest();
    }

    public function test_an_institution_registration_creates_the_institution_and_its_admin(): void
    {
        $response = $this->post('/register', [
            'role' => 'institution',
            'username' => 'inst_admin',
            'email' => 'institution@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'official_name' => 'إدارة المؤسسة',
            'institution_name' => 'مؤسسة تجريبية',
            'institution_code' => 'TEST-01',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        $this->assertDatabaseHas('institutions', ['code' => 'TEST-01']);

        $user = User::where('email', 'institution@example.com')->firstOrFail();
        $this->assertSame('institution', $user->role->value);
        $this->assertNotNull($user->institution_id);
    }
}
