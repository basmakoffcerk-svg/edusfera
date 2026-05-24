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
            $table->unsignedTinyInteger('student_rating')->nullable()->after('payment_status');
            $table->text('student_feedback')->nullable()->after('student_rating');
            $table->boolean('is_public_review')->default(false)->after('student_feedback');
            $table->timestamp('feedback_submitted_at')->nullable()->after('is_public_review');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropColumn([
                'student_rating',
                'student_feedback',
                'is_public_review',
                'feedback_submitted_at',
            ]);
        });
    }
};
