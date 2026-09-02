<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Identity\Models\SandboxSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SandboxTest extends TestCase
{
    public function test_landing_page_shows_system_test_button(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('اختبار النظام');
        $response->assertSee('sandbox-hub-modal');
    }

    public function test_student_gateway_renders_properly(): void
    {
        $response = $this->get(route('sandbox.student.gateway'));

        $response->assertStatus(200);
        $response->assertSee('بوابة اختبار النظام');
        $response->assertSee('Face Verification');
        $response->assertSee('15 سؤال');
        $response->assertSee('7 دقائق');
    }

    public function test_student_exam_renders_with_15_questions_and_7_minutes(): void
    {
        $response = $this->get(route('sandbox.student.exam', ['face_match' => 1]));

        $response->assertStatus(200);
        $response->assertSee('اختبار النظام فائق الحماية');
        $response->assertSee('Offline Freeze');
        $response->assertSee('sandboxExamRunner');
    }

    public function test_teacher_sandbox_renders_properly(): void
    {
        $response = $this->get(route('sandbox.teacher'));

        $response->assertStatus(200);
        $response->assertSee('بيئة المعلم التجريبية');
        $response->assertSee('المراقبة الحية');
    }

    public function test_institution_sandbox_renders_properly(): void
    {
        $response = $this->get(route('sandbox.institution'));

        $response->assertStatus(200);
        $response->assertSee('مركز الرصد الأمني والمؤشرات المؤسسية');
    }

    public function test_family_survey_renders_properly(): void
    {
        $response = $this->get(route('sandbox.family'));

        $response->assertStatus(200);
        $response->assertSee('استبيان أولياء الأمور');
        $response->assertSee('تجميد انقطاع الإنترنت');
    }

    public function test_survey_submission_and_sentiment_analysis(): void
    {
        $this->withoutMiddleware();

        $payload = [
            'role' => 'student',
            'name' => 'فيصل الاختبارات',
            'email' => 'faisal@example.com',
            'organization' => 'كلية الهندسة',
            'overall_rating' => 5,
            'support_anti_cheat' => 'strongly_support',
            'face_match_rating' => 5,
            'time_freeze_rating' => 5,
            'security_rating' => 5,
            'usability_rating' => 5,
            'feedback_text' => 'فكرة تجميد الوقت عند انقطاع الإنترنت ممتازة ومطمئنة للغاية، وتمنع الغش والظلم!',
        ];

        $response = $this->postJson(route('sandbox.surveys.store'), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('sentiment.sentiment_label', 'positive');

        $this->assertDatabaseHas('sandbox_surveys', [
            'name' => 'فيصل الاختبارات',
            'role' => 'student',
            'sentiment_label' => 'positive',
        ]);
    }

    public function test_surveys_dashboard_and_export(): void
    {
        $dashboardResponse = $this->get(route('sandbox.surveys'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('لوحة تحليلات الاستبيانات');

        $exportResponse = $this->get(route('sandbox.surveys.export'));
        $exportResponse->assertStatus(200);
        $exportResponse->assertSee('التقرير التحليلي الشامل');
    }
}
