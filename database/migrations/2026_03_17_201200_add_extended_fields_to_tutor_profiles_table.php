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
            $table->text('education_summary')->nullable()->after('bio');
            $table->text('teaching_methodology')->nullable()->after('education_summary');
            $table->text('achievements')->nullable()->after('teaching_methodology');
            $table->text('homework_policy')->nullable()->after('achievements');
            $table->json('lesson_formats')->nullable()->after('homework_policy');
            $table->json('lesson_languages')->nullable()->after('lesson_formats');
            $table->string('intro_video_url')->nullable()->after('lesson_languages');
            $table->unsignedSmallInteger('trial_lesson_minutes')->nullable()->after('intro_video_url');
            $table->timestamp('onboarding_completed_at')->nullable()->after('verification_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'education_summary',
                'teaching_methodology',
                'achievements',
                'homework_policy',
                'lesson_formats',
                'lesson_languages',
                'intro_video_url',
                'trial_lesson_minutes',
                'onboarding_completed_at',
            ]);
        });
    }
};
