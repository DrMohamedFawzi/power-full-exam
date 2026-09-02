<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment\Authoring;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_only_their_own_published_exams_in_the_results_index(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $otherTeacher = User::factory()->teacher()->create(['is_approved' => true]);

        $ownExam = Exam::factory()->create(['created_by' => $teacher->id, 'title' => 'اختباري']);
        Exam::factory()->create(['created_by' => $otherTeacher->id, 'title' => 'اختبار غيري']);

        $response = $this->actingAs($teacher)->get(route('teacher.results.index'));

        $response->assertOk();
        $response->assertSee('اختباري');
        $response->assertDontSee('اختبار غيري');
    }

    public function test_results_show_page_lists_submissions_with_integrity_and_violations(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->create(['created_by' => $teacher->id]);
        $student = User::factory()->student()->create(['official_name' => 'الطالبة سارة']);

        ExamSession::factory()->for($exam)->for($student, 'student')
            ->status(SessionStatus::Submitted)
            ->integrity(77)
            ->create(['submitted_at' => now(), 'score' => 5]);

        $response = $this->actingAs($teacher)->get(route('teacher.results.show', $exam));

        $response->assertOk();
        $response->assertSee('الطالبة سارة');
    }
}
