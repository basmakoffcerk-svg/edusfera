<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('feedback')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['lesson_id', 'reviewer_id']);
        });

        // Migrate existing review data from lessons table
        if (Schema::hasColumn('lessons', 'student_rating')) {
            $timestamp = now();

            DB::table('lesson_reviews')->insertUsing(
                ['lesson_id', 'reviewer_id', 'rating', 'feedback', 'is_public', 'submitted_at', 'created_at', 'updated_at'],
                DB::table('lessons')
                    ->selectRaw(
                        'id, student_id, student_rating, student_feedback, is_public_review, COALESCE(feedback_submitted_at, created_at), ?, ?',
                        [$timestamp, $timestamp],
                    )
                    ->whereNotNull('student_rating')
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_reviews');
    }
};
