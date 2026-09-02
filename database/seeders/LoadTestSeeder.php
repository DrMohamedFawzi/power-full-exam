<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LoadTestSeeder extends Seeder
{
    private const TOTAL_STUDENTS   = 50_000;
    private const CHUNK_SIZE       = 1_000;   // طلاب في كل batch
    private const QUESTION_COUNT   = 10;
    private const EXAM_DURATION    = 60;

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════╗');
        $this->command->info('║   🚀  Aegis-X Load Test Seeder                   ║');
        $this->command->info('║   Target: 50,000 students — Chunk: 1,000/batch   ║');
        $this->command->info('╚══════════════════════════════════════════════════╝');
        $this->command->info('');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('SET unique_checks=0;');
        DB::statement('SET autocommit=0;');

        $now = Carbon::now()->toDateTimeString();

        // ─── 1. Institution ───────────────────────────────────────────
        $this->command->info('1/5  إنشاء مؤسسة الاختبار...');
        $institutionId = DB::table('institutions')->insertGetId([
            'name'          => 'مؤسسة اختبار الضغط',
            'code'          => 'LOADTEST',
            'contact_email' => 'loadtest@aegis-x.test',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
        $this->command->info("   ✅ Institution ID = {$institutionId}");

        // ─── 2. Teacher (مطلوب لإنشاء exam) ──────────────────────────
        $this->command->info('2/5  إنشاء حساب المعلم...');
        $teacherId = DB::table('users')->insertGetId([
            'username'       => 'teacher_loadtest',
            'email'          => 'teacher@aegis-x.test',
            'password'       => Hash::make('password123'),
            'official_name'  => 'معلم الاختبار',
            'role'           => 'teacher',
            'institution_id' => $institutionId,
            'is_approved'    => true,
            'qr_token'       => Str::random(64),
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
        $this->command->info("   ✅ Teacher ID = {$teacherId}");

        // ─── 3. Classroom ─────────────────────────────────────────────
        $this->command->info('3/5  إنشاء الفصل والامتحان...');
        $classroomId = DB::table('classrooms')->insertGetId([
            'code'           => 'LOADTEST-CLASS',
            'name'           => 'فصل اختبار الضغط',
            'teacher_id'     => $teacherId,
            'institution_id' => $institutionId,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // ─── 4. Exam ──────────────────────────────────────────────────
        $examId = DB::table('exams')->insertGetId([
            'code'             => 'LOADTEST-EXAM-001',
            'title'            => 'امتحان اختبار الضغط الأقصى',
            'description'      => 'امتحان مخصص لاختبار تحمّل النظام بـ 50,000 طالب متزامن',
            'classroom_id'     => $classroomId,
            'created_by'       => $teacherId,
            'duration_minutes' => self::EXAM_DURATION,
            'security_level'   => 'off',   // لا قيود أثناء الاختبار
            'mode'             => 'official',
            'status'           => 'published',
            'shuffle_questions'=> false,
            'max_attempts'     => 999,    // السماح بمحاولات متعددة لـ k6
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        // ─── 5. Questions ─────────────────────────────────────────────
        $questions = [];
        for ($i = 1; $i <= self::QUESTION_COUNT; $i++) {
            $questions[] = [
                'exam_id'        => $examId,
                'position'       => $i,
                'type'           => 'multiple_choice',
                'prompt'         => "سؤال اختبار الضغط رقم {$i}: ما هي الإجابة الصحيحة؟",
                'options'        => json_encode(['أ', 'ب', 'ج', 'د']),
                'correct_answer' => json_encode(['0']),
                'points'         => 1.00,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }
        DB::table('questions')->insert($questions);
        $this->command->info("   ✅ Exam ID = {$examId}  |  Questions = " . self::QUESTION_COUNT);

        // ─── 6. 50,000 Students (Chunk Batch Insert) ─────────────────
        $this->command->info('');
        $this->command->info('4/5  توليد 50,000 طالب (Chunk Batch Insert)...');
        $this->command->warn('   ⚡ Hash-Once trick: كلمة المرور تُشفَّر مرة واحدة فقط!');

        // السر الرئيسي: تشفير مرة واحدة → نسخ لكل الطلاب
        $hashedPassword = Hash::make('password123');

        $bar      = $this->command->getOutput()->createProgressBar(self::TOTAL_STUDENTS);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  ⏱  %elapsed:6s%/%estimated:-6s%');
        $bar->start();

        $chunks   = (int) ceil(self::TOTAL_STUDENTS / self::CHUNK_SIZE);
        $inserted = 0;

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $users = [];
            $start = $chunk * self::CHUNK_SIZE + 1;
            $end   = min($start + self::CHUNK_SIZE - 1, self::TOTAL_STUDENTS);

            for ($i = $start; $i <= $end; $i++) {
                $users[] = [
                    'username'       => "student{$i}",
                    'email'          => "student{$i}@aegis-x.test",
                    'password'       => $hashedPassword,
                    'official_name'  => "طالب رقم {$i}",
                    'role'           => 'student',
                    'institution_id' => $institutionId,
                    'is_approved'    => true,
                    'qr_token'       => Str::random(64),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            DB::table('users')->insert($users);
            $inserted += count($users);
            $bar->advance(count($users));

            // Commit كل 5 chunks لتفادي transaction طويلة جداً
            if ($chunk % 5 === 0) {
                DB::statement('COMMIT;');
                DB::statement('SET autocommit=0;');
            }
        }

        DB::statement('COMMIT;');
        $bar->finish();
        $this->command->info('');
        $this->command->info("   ✅ تم إدخال {$inserted} طالب بنجاح!");

        // ─── 7. إعادة ضبط MySQL ───────────────────────────────────────
        DB::statement('SET unique_checks=1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::statement('SET autocommit=1;');

        // ─── ملخص نهائي ───────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('5/5  ملخص بيانات الاختبار:');
        $this->command->table(
            ['Entity', 'Value', 'Usage in k6'],
            [
                ['EXAM_ID',         $examId,           'k6 --env EXAM_ID=' . $examId],
                ['Classroom Code',  'LOADTEST-CLASS',  'للتسجيل يدوياً'],
                ['Teacher Email',   'teacher@aegis-x.test', 'للتحقق من Dashboard'],
                ['Student Pattern', 'student{N}@aegis-x.test', 'N من 1 إلى 50,000'],
                ['Password',        'password123',     'لكل الحسابات'],
                ['Total Students',  number_format(self::TOTAL_STUDENTS), '✅ جاهزون'],
            ]
        );

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════╗');
        $this->command->info('║  🎉 البيئة جاهزة! الخطوة التالية:               ║');
        $this->command->info('║                                                  ║');
        $this->command->info("║  k6 run load-test\\k6-script.js \\               ║");
        $this->command->info("║    --env BASE_URL=http://localhost \\             ║");
        $this->command->info("║    --env EXAM_ID={$examId}                              ║");
        $this->command->info('╚══════════════════════════════════════════════════╝');
        $this->command->info('');
    }
}
