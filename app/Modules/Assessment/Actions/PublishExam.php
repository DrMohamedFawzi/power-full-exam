<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;
use Illuminate\Validation\ValidationException;

/**
 * Publishing is the coherence gate: an exam may not go live half-built.
 */
final class PublishExam
{
    public function __invoke(Exam $exam): Exam
    {
        $questions = $exam->questions;

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'exam' => 'لا يمكن نشر اختبار بلا أسئلة. أضف سؤالاً واحداً على الأقل.',
            ]);
        }

        if ($exam->duration_minutes <= 0) {
            throw ValidationException::withMessages([
                'exam' => 'يجب أن تكون مدة الاختبار أكبر من صفر.',
            ]);
        }

        foreach ($questions as $question) {
            if ((float) $question->points <= 0) {
                throw ValidationException::withMessages([
                    'exam' => "السؤال رقم {$question->position} يجب أن تكون درجته أكبر من صفر.",
                ]);
            }

            if ($question->type->isAutoGradable() && empty($question->correct_answer)) {
                throw ValidationException::withMessages([
                    'exam' => "السؤال رقم {$question->position} بلا إجابة صحيحة محدّدة.",
                ]);
            }
        }

        $exam->update(['status' => ExamStatus::Published->value]);

        return $exam;
    }
}
