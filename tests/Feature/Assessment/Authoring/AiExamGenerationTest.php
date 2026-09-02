<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment\Authoring;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiExamGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'topic' => 'أساسيات البرمجة',
            'count' => 2,
            'difficulty' => 'medium',
            'language' => 'ar',
            'types' => ['multiple_choice'],
        ];
    }

    public function test_generation_is_refused_when_no_api_key_is_configured(): void
    {
        config(['aegis.ai.api_key' => null, 'aegis.ai.enabled' => true]);
        Http::fake();

        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.exams.ai.generate', $exam), $this->payload());

        $response->assertSessionHasErrors('ai');
        Http::assertNothingSent();
    }

    public function test_generation_succeeds_and_shows_a_preview(): void
    {
        config(['aegis.ai.api_key' => 'test-key', 'aegis.ai.enabled' => true]);

        $questionsJson = json_encode([
            [
                'type' => 'multiple_choice',
                'prompt' => 'ما هي نتيجة 1 + 1؟',
                'options' => ['1', '2', '3'],
                'correct_answer' => [1],
                'explanation' => 'لأن 1 + 1 = 2',
                'points' => 1,
            ],
        ]);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $questionsJson]]]],
                ],
            ]),
        ]);

        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.exams.ai.generate', $exam), $this->payload());

        $response->assertOk();
        $response->assertSee('ما هي نتيجة 1 + 1؟');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));
    }

    public function test_generation_failure_is_reported_in_arabic_without_a_500(): void
    {
        config(['aegis.ai.api_key' => 'test-key', 'aegis.ai.enabled' => true]);
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response(['error' => 'boom'], 500)]);

        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.exams.ai.generate', $exam), $this->payload());

        $response->assertStatus(302);
        $response->assertSessionHasErrors('ai');
    }

    public function test_teacher_can_persist_reviewed_ai_questions(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->postJson(route('teacher.exams.ai.store', $exam), [
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'prompt' => 'سؤال تمت مراجعته',
                    'options' => ['أ', 'ب'],
                    'correct_answer' => [0],
                    'explanation' => null,
                    'points' => 1,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['count' => 1]);
        $this->assertDatabaseHas('questions', ['exam_id' => $exam->id, 'prompt' => 'سؤال تمت مراجعته']);
    }
}
