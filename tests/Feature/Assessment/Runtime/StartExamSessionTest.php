<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment\Runtime;

use App\Modules\Assessment\Actions\StartExamSession;
use App\Modules\Assessment\Enums\ExamMode;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Data\FingerprintPayload;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class StartExamSessionTest extends TestCase
{
    use RefreshDatabase;

    private function action(): StartExamSession
    {
        return app(StartExamSession::class);
    }

    private function fingerprint(): FingerprintPayload
    {
        return new FingerprintPayload;
    }

    public function test_creates_a_new_session_for_a_mock_student_exam(): void
    {
        $student = User::factory()->student()->create();
        $exam = Exam::factory()->mode(ExamMode::MockStudent)->create(['created_by' => $student->id, 'max_attempts' => 1]);

        $session = $this->action()($student, $exam, null, $this->fingerprint(), '127.0.0.1');

        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => SessionStatus::Active->value,
        ]);
        $this->assertNotEmpty($session->shuffle_seed);
        $this->assertSame(100, $session->integrity_index);
    }

    public function test_resumes_an_existing_active_session_instead_of_creating_a_second_one(): void
    {
        $student = User::factory()->student()->create();
        $exam = Exam::factory()->mode(ExamMode::MockStudent)->create(['created_by' => $student->id, 'max_attempts' => 3]);

        $first = $this->action()($student, $exam, null, $this->fingerprint(), '127.0.0.1');
        $second = $this->action()($student, $exam, null, $this->fingerprint(), '127.0.0.1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ExamSession::query()->where('exam_id', $exam->id)->count());
    }

    public function test_refuses_to_start_a_session_for_a_closed_exam(): void
    {
        $student = User::factory()->student()->create();
        $exam = Exam::factory()->mode(ExamMode::MockStudent)->closed()->create(['created_by' => $student->id]);

        $this->expectException(HttpException::class);

        $this->action()($student, $exam, null, $this->fingerprint(), '127.0.0.1');
    }

    public function test_respects_max_attempts(): void
    {
        $student = User::factory()->student()->create();
        $exam = Exam::factory()->mode(ExamMode::MockStudent)->maxAttempts(1)->create(['created_by' => $student->id]);

        ExamSession::factory()->status(SessionStatus::Submitted)->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->action()($student, $exam, null, $this->fingerprint(), '127.0.0.1');
    }

    public function test_binds_the_device_via_identity_bind_device(): void
    {
        $student = User::factory()->student()->create();
        $exam = Exam::factory()->mode(ExamMode::MockStudent)->create(['created_by' => $student->id]);

        $session = $this->action()($student, $exam, 'device-hash-123', $this->fingerprint(), '10.0.0.1');

        $this->assertNotNull($session->device_id);
        $this->assertDatabaseHas('user_devices', [
            'id' => $session->device_id,
            'user_id' => $student->id,
            'device_hash' => 'device-hash-123',
        ]);
    }
}
