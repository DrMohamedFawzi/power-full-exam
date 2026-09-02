<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_hash', 64);
                $table->string('label', 150)->default('جهاز غير معروف');
                $table->string('status', 20)->default('active');
                $table->string('last_ip', 45)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'device_hash']);
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('device_fingerprints')) {
            Schema::create('device_fingerprints', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_device_id')->constrained('user_devices')->cascadeOnDelete();
                $table->text('user_agent')->nullable();
                $table->string('screen_resolution', 32)->nullable();
                $table->string('timezone', 64)->nullable();
                $table->string('canvas_hash', 64)->nullable();
                $table->string('webgl_vendor', 255)->nullable();
                $table->string('webgl_renderer', 255)->nullable();
                $table->boolean('is_headless')->default(false);
                $table->json('raw')->nullable();
                $table->timestamps();

                $table->index('user_device_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_fingerprints');
        Schema::dropIfExists('user_devices');
    }
};
