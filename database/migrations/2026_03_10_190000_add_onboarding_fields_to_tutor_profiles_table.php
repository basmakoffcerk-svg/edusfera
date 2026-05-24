<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->json('audiences')->nullable()->after('subjects');
            $table->string('diploma_path')->nullable()->after('avatar_path');
            $table->string('verification_status')->default('pending')->after('is_verified');
            $table->timestamp('verification_submitted_at')->nullable()->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'audiences',
                'diploma_path',
                'verification_status',
                'verification_submitted_at',
            ]);
        });
    }
};
