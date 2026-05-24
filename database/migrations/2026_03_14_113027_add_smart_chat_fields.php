<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->foreignId('sender_id')->nullable()->change();
            $table->boolean('is_system')->default(false)->after('message');
            $table->json('meta')->nullable()->after('is_system');
        });

        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->string('telegram_username')->nullable()->after('avatar_path');
            $table->unsignedInteger('contact_bypass_attempts')->default(0)->after('rating_avg');
            $table->timestamp('search_penalized_until')->nullable()->after('contact_bypass_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'telegram_username',
                'contact_bypass_attempts',
                'search_penalized_until',
            ]);
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropColumn([
                'is_system',
                'meta',
            ]);
            $table->foreignId('sender_id')->nullable(false)->change();
        });
    }
};
