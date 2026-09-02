<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo Identity data: one institution with an approved admin, an approved and
 * a pending teacher, and a handful of students (some institution-bound, some
 * independent). Wired into the app by DatabaseSeeder, not by this file.
 */
final class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::factory()->create([
            'name' => 'مدرسة النجاح النموذجية',
            'code' => 'AL-NAJAH',
            'contact_email' => 'admin@al-najah.example',
        ]);

        User::factory()->institution()->create([
            'username' => 'al_najah_admin',
            'email' => 'institution@aegis-x.test',
            'official_name' => 'إدارة مدرسة النجاح',
            'institution_id' => $institution->id,
        ]);

        User::factory()->teacher()->create([
            'username' => 'approved_teacher',
            'email' => 'teacher@aegis-x.test',
            'official_name' => 'أ. سامي الفوزي',
            'institution_id' => $institution->id,
            'is_approved' => true,
        ]);

        User::factory()->teacher()->pending()->create([
            'username' => 'pending_teacher',
            'email' => 'pending.teacher@aegis-x.test',
            'official_name' => 'أ. ريم القاسم',
            'institution_id' => $institution->id,
        ]);

        User::factory()->student()->create([
            'username' => 'student_one',
            'email' => 'student@aegis-x.test',
            'official_name' => 'خالد المصري',
            'institution_id' => $institution->id,
        ]);

        User::factory()->student()->count(3)->create();
    }
}
