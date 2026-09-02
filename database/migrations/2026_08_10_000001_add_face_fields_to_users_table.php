<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'face_descriptor')) {
                $table->json('face_descriptor')->nullable()->after('avatar_path');
            }

            if (! Schema::hasColumn('users', 'photo_status')) {
                // null = لم تُرفع بعد | pending = قيد المراجعة | approved = معتمدة | rejected = مرفوضة
                $table->string('photo_status', 20)->nullable()->after('face_descriptor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['face_descriptor', 'photo_status']);
        });
    }
};
