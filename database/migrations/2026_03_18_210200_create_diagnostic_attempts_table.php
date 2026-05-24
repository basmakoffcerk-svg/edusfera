<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_track_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 120);
            $table->string('exam_type', 32);
            $table->string('source', 32)->default('platform');
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('max_score')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->timestamp('taken_at');
            $table->json('breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_goal_id', 'taken_at']);
            $table->index(['student_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_attempts');
    }
};
