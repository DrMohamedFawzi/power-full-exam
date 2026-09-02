<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('violations')) {
            Schema::create('violations', function (Blueprint $table): void {
                $table->id();
                $table->integer('exam_session_id')->index();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->string('type', 60)->index();
                $table->string('severity', 20)->default('medium');
                $table->unsignedTinyInteger('penalty')->default(0);
                $table->text('details')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('detected_at')->useCurrent();
                $table->timestamps();

                $table->index(['exam_session_id', 'severity']);
            });
        }

        if (!Schema::hasTable('heartbeats')) {
            Schema::create('heartbeats', function (Blueprint $table): void {
                $table->id();
                $table->integer('exam_session_id')->index();
                $table->string('status', 20);
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->timestamp('detected_at')->useCurrent();

                $table->index(['exam_session_id', 'detected_at']);
            });
        }

        if (!Schema::hasTable('keystroke_samples')) {
            Schema::create('keystroke_samples', function (Blueprint $table): void {
                $table->id();
                $table->integer('exam_session_id')->index();
                $table->decimal('mean_interval_ms', 8, 2);
                $table->decimal('std_deviation_ms', 8, 2);
                $table->unsignedInteger('sample_size');
                $table->decimal('anomaly_score', 5, 2)->default(0);
                $table->timestamp('captured_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('keystroke_samples');
        Schema::dropIfExists('heartbeats');
        Schema::dropIfExists('violations');
    }
};
