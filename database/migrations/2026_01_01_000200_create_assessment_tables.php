<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->foreignId('classroom_id')->nullable()->constrained(Schema::hasTable('classrooms') ? 'classrooms' : 'classes')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->unsignedSmallInteger('duration_minutes')->default(60);
                $table->string('security_level', 20)->default('strict');
                $table->string('mode', 20)->default('official');
                $table->string('status', 20)->default('draft');
                $table->boolean('shuffle_questions')->default(true);
                $table->boolean('preserve_time_offline')->default(false);
                $table->unsignedSmallInteger('max_attempts')->default(1);
                $table->timestamp('opens_at')->nullable();
                $table->timestamp('closes_at')->nullable();
                $table->timestamps();

                $table->index(['classroom_id', 'status']);
                $table->index(['created_by', 'status']);
            });
        }

        if (!Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table): void {
                $table->id();
                $table->integer('exam_id')->index();
                $table->unsignedSmallInteger('position')->default(0);
                $table->string('type', 20)->default('multiple_choice');
                $table->text('prompt');
                $table->json('options')->nullable();
                $table->json('correct_answer');
                $table->text('explanation')->nullable();
                $table->decimal('points', 5, 2)->default(1);
                $table->timestamps();

                $table->index(['exam_id', 'position']);
            });
        }

        if (!Schema::hasTable('exam_sessions')) {
            Schema::create('exam_sessions', function (Blueprint $table): void {
                $table->id();
                $table->integer('exam_id')->index();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('device_id')->nullable()->constrained('user_devices')->nullOnDelete();
                $table->string('status', 20)->default('active');
                $table->string('shuffle_seed', 32);
                $table->decimal('score', 5, 2)->nullable();
                $table->unsignedTinyInteger('integrity_index')->default(100);
                $table->unsignedInteger('offline_seconds')->default(0);
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->index(['exam_id', 'status']);
                $table->index(['student_id', 'status']);
            });
        }

        if (!Schema::hasTable('exam_answers')) {
            Schema::create('exam_answers', function (Blueprint $table): void {
                $table->id();
                $table->integer('exam_session_id')->index();
                $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
                $table->json('answer')->nullable();
                $table->boolean('is_correct')->nullable();
                $table->decimal('points_awarded', 5, 2)->default(0);
                $table->unsignedInteger('time_spent_seconds')->default(0);
                $table->timestamps();

                $table->unique(['exam_session_id', 'question_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('exams');
    }
};
