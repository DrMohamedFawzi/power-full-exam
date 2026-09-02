<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('classrooms')) {
            Schema::create('classrooms', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->boolean('is_archived')->default(false);
                $table->timestamps();

                $table->index(['teacher_id', 'is_archived']);
            });
        }

        if (!Schema::hasTable('enrollment_requests')) {
            Schema::create('enrollment_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('classroom_id')->constrained(Schema::hasTable('classrooms') ? 'classrooms' : 'classes')->cascadeOnDelete();
                $table->string('status', 20)->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('rejection_reason', 255)->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'classroom_id']);
                $table->index(['classroom_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_requests');
        Schema::dropIfExists('classrooms');
    }
};
