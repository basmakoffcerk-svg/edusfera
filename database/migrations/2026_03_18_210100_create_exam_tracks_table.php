<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_tracks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->string('format', 32)->default('individual');
            $table->string('status', 32)->default('draft');
            $table->unsignedTinyInteger('weekly_sessions_target')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['student_goal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_tracks');
    }
};
