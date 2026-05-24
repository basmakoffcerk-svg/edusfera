<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->text('tutor_report_summary')->nullable()->after('notes');
            $table->text('tutor_report_focus')->nullable()->after('tutor_report_summary');
            $table->text('tutor_next_step')->nullable()->after('tutor_report_focus');
            $table->text('tutor_homework_summary')->nullable()->after('tutor_next_step');
            $table->unsignedSmallInteger('tutor_report_score')->nullable()->after('tutor_homework_summary');
            $table->timestamp('tutor_reported_at')->nullable()->after('tutor_report_score');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropColumn([
                'tutor_report_summary',
                'tutor_report_focus',
                'tutor_next_step',
                'tutor_homework_summary',
                'tutor_report_score',
                'tutor_reported_at',
            ]);
        });
    }
};
