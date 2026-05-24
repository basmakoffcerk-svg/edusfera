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
            $table->json('exam_specializations')->nullable()->after('lesson_languages');
            $table->unsignedSmallInteger('average_score_growth')->nullable()->after('exam_specializations');
            $table->unsignedInteger('students_prepared_count')->default(0)->after('average_score_growth');
            $table->unsignedSmallInteger('max_recent_score')->nullable()->after('students_prepared_count');
            $table->boolean('diagnostic_supported')->default(false)->after('max_recent_score');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'exam_specializations',
                'average_score_growth',
                'students_prepared_count',
                'max_recent_score',
                'diagnostic_supported',
            ]);
        });
    }
};
