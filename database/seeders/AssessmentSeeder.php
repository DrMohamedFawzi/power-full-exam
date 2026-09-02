<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Assessment\Enums\QuestionType;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamAnswer;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data for exam authoring: a published exam with one of every question
 * type (including an ungraded short answer, for the grading queue screen)
 * plus a draft exam still being built.
 */
class AssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::factory()->teacher()->create([
            'official_name' => 'أ. ريم العتيبي',
            'is_approved' => true,
        ]);

        $classroom = Classroom::factory()->for($teacher, 'teacher')->create();

        $exam = Exam::factory()->for($classroom)->create([
            'created_by' => $teacher->id,
            'title' => 'اختبار الفصل الأول - أساسيات البرمجة',
        ]);

        $questions = [
            Question::factory()->for($exam)->position(1)->create(['points' => 2]),
            Question::factory()->for($exam)->position(2)->trueFalse()->create(),
            Question::factory()->for($exam)->position(3)->multipleSelect()->create(),
            Question::factory()->for($exam)->position(4)->shortAnswer()->create(),
        ];

        $student = User::factory()->student()->create(['official_name' => 'الطالب ماجد القرني']);

        $session = ExamSession::factory()
            ->for($exam)
            ->for($student, 'student')
            ->status(SessionStatus::Submitted)
            ->integrity(92)
            ->create(['submitted_at' => now(), 'score' => 3]);

        foreach ($questions as $question) {
            $isShortAnswer = $question->type === QuestionType::ShortAnswer;

            ExamAnswer::factory()->create([
                'exam_session_id' => $session->id,
                'question_id' => $question->id,
                'answer' => $isShortAnswer ? ['إجابة الطالب النصية'] : $question->correct_answer,
                'is_correct' => $isShortAnswer ? null : true,
                'points_awarded' => $isShortAnswer ? 0 : $question->points,
            ]);
        }

        Exam::factory()->draft()->for($classroom)->create([
            'created_by' => $teacher->id,
            'title' => 'اختبار قيد الإعداد',
        ]);
    }
}
