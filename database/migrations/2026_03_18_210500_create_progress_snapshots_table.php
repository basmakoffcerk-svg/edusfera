<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_track_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('snapshot_date');
            $table->unsignedSmallInteger('current_score')->nullable();
            $table->unsignedSmallInteger('predicted_score')->nullable();
            $table->unsignedSmallInteger('target_score')->nullable();
            $table->unsignedSmallInteger('completed_topics_count')->default(0);
            $table->unsignedSmallInteger('active_skill_gaps_count')->default(0);
            $table->text('summary')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['student_goal_id', 'snapshot_date']);
            $table->index(['student_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_snapshots');
    }
};
