<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sandbox_surveys', function (Blueprint $table): void {
            $table->id();
            $table->string('role', 32); // student, teacher, institution, family
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('organization')->nullable();
            $table->unsignedTinyInteger('overall_rating')->default(5); // 1-5
            $table->string('support_anti_cheat', 32)->default('strongly_support'); // strongly_support, support, neutral, oppose, strongly_oppose
            $table->unsignedTinyInteger('face_match_rating')->nullable();
            $table->unsignedTinyInteger('time_freeze_rating')->nullable();
            $table->unsignedTinyInteger('security_rating')->nullable();
            $table->unsignedTinyInteger('usability_rating')->nullable();
            $table->text('feedback_text')->nullable();
            $table->decimal('sentiment_score', 4, 2)->default(0.85); // -1.00 to 1.00
            $table->string('sentiment_label', 32)->default('positive'); // positive, neutral, negative
            $table->json('detected_keywords')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['role', 'sentiment_label']);
            $table->index('support_anti_cheat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_surveys');
    }
};
