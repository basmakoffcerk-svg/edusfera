<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_gaps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 120);
            $table->string('topic', 160);
            $table->string('severity', 16)->default('medium');
            $table->string('status', 16)->default('open');
            $table->timestamp('last_detected_at')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamps();

            $table->index(['student_goal_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_gaps');
    }
};
