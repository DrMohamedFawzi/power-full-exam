<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Assessment\Enums\ExamMode;
use App\Modules\Assessment\Enums\SecurityLevel;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Models\Question;
use App\Modules\Identity\Models\User;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Modules\Proctoring\Models\Heartbeat;
use App\Modules\Proctoring\Models\KeystrokeSample;
use App\Modules\Proctoring\Models\Violation;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

/**
 * Demo data for the runtime + proctoring pipeline: one strict exam with a
 * question bank, a clean session, a flagged session with escalating
 * violations, and a terminated one — enough to see every state in the
 * teacher's live monitor and the student's results without a real exam run.
 */
class ProctoringSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::factory()->teacher()->create([
            'username' => 'demo.teacher',
            'email' => 'demo.teacher@aegis-x.test',
            'official_name' => 'أ. سالم المعلّم',
        ]);

        $exam = Exam::factory()->create([
            'code' => 'DEMO-EXAM-01',
            'title' => 'اختبار تجريبي — المراقبة والنزاهة',
            'created_by' => $teacher->id,
            'security_level' => SecurityLevel::Strict->value,
            'mode' => ExamMode::Official->value,
            'max_attempts' => 2,
        ]);

        Question::factory()->count(5)->sequence(
            fn (Sequence $sequence) => ['position' => $sequence->index],
        )->create(['exam_id' => $exam->id]);

        Question::factory()->trueFalse()->create(['exam_id' => $exam->id, 'position' => 5]);
        Question::factory()->multipleSelect()->create(['exam_id' => $exam->id, 'position' => 6]);
        Question::factory()->shortAnswer()->create(['exam_id' => $exam->id, 'position' => 7]);

        $this->cleanSession($exam);
        $this->flaggedSession($exam);
        $this->terminatedSession($exam);
    }

    private function cleanSession(Exam $exam): void
    {
        $student = User::factory()->student()->create([
            'username' => 'demo.student.clean',
            'official_name' => 'سارة الطالبة',
        ]);

        ExamSession::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'integrity_index' => 100,
        ]);
    }

    private function flaggedSession(Exam $exam): void
    {
        $student = User::factory()->student()->create([
            'username' => 'demo.student.flagged',
            'official_name' => 'خالد الطالب',
        ]);

        $session = ExamSession::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'offline_seconds' => 45,
        ]);

        collect([ViolationType::TabSwitch, ViolationType::WindowBlur, ViolationType::CopyAttempt])
            ->each(fn (ViolationType $type) => Violation::query()->create([
                'exam_session_id' => $session->id,
                'student_id' => $student->id,
                'type' => $type->value,
                'severity' => $type->severity()->value,
                'penalty' => $type->severity()->penalty(),
                'detected_at' => now(),
            ]));

        $session->update(['integrity_index' => 100 - Violation::query()->where('exam_session_id', $session->id)->sum('penalty')]);

        Heartbeat::factory()->offline(15)->create(['exam_session_id' => $session->id]);
        KeystrokeSample::factory()->create(['exam_session_id' => $session->id]);
    }

    private function terminatedSession(Exam $exam): void
    {
        $student = User::factory()->student()->create([
            'username' => 'demo.student.terminated',
            'official_name' => 'ليلى الطالبة',
        ]);

        $session = ExamSession::factory()->status(SessionStatus::Terminated)->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'integrity_index' => 10,
            'score' => 4,
            'submitted_at' => now(),
        ]);

        Violation::query()->create([
            'exam_session_id' => $session->id,
            'student_id' => $student->id,
            'type' => ViolationType::ForbiddenDevice->value,
            'severity' => ViolationType::ForbiddenDevice->severity()->value,
            'penalty' => ViolationType::ForbiddenDevice->severity()->penalty(),
            'detected_at' => now(),
        ]);
    }
}
