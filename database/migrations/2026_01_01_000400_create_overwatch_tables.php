<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('threats')) {
            Schema::create('threats', function (Blueprint $table): void {
                $table->id();
                $table->string('ip_address', 45)->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('attack_type', 50)->index();
                $table->string('severity', 20)->default('medium');
                $table->string('request_path', 255)->nullable();
                $table->text('payload')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('detected_at')->useCurrent();

                $table->index(['attack_type', 'detected_at']);
            });
        }

        if (!Schema::hasTable('banned_ips')) {
            Schema::create('banned_ips', function (Blueprint $table): void {
                $table->id();
                $table->string('ip_address', 45)->unique();
                $table->string('reason', 255)->nullable();
                $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('banned_until')->nullable();
                $table->timestamps();

                $table->index('banned_until');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_ips');
        Schema::dropIfExists('threats');
    }
};
