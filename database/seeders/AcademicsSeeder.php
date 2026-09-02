<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data for the Academics module: one institution, an approved and a
 * pending teacher, a handful of classrooms, and students in every enrollment
 * state so every screen has something real to show.
 */
class AcademicsSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::factory()->create([
            'name' => 'مدرسة الأمل النموذجية',
        ]);

        $approvedTeacher = User::factory()->teacher()->create([
            'official_name' => 'أ. سالم الحربي',
            'institution_id' => $institution->id,
            'is_approved' => true,
        ]);

        User::factory()->teacher()->pending()->create([
            'official_name' => 'أ. منى القحطاني',
            'institution_id' => $institution->id,
        ]);

        $classrooms = Classroom::factory()
            ->count(3)
            ->for($approvedTeacher, 'teacher')
            ->create(['institution_id' => $institution->id]);

        $students = User::factory()
            ->count(6)
            ->student()
            ->create(['institution_id' => $institution->id]);

        foreach ($classrooms as $index => $classroom) {
            EnrollmentRequest::factory()->approved()->create([
                'classroom_id' => $classroom->id,
                'student_id' => $students[$index]->id,
                'reviewed_by' => $approvedTeacher->id,
            ]);
        }

        EnrollmentRequest::factory()->create([
            'classroom_id' => $classrooms->first()->id,
            'student_id' => $students[3]->id,
        ]);

        EnrollmentRequest::factory()->rejected()->create([
            'classroom_id' => $classrooms->first()->id,
            'student_id' => $students[4]->id,
            'reviewed_by' => $approvedTeacher->id,
            'rejection_reason' => 'الطالب غير مسجل رسميًا في هذا الفصل.',
        ]);
    }
}
