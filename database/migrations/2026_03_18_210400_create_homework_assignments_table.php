<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_goal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->text('instructions')->nullable();
            $table->string('source', 16)->default('tutor');
            $table->string('status', 16)->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['student_goal_id', 'status']);
            $table->index(['lesson_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_assignments');
    }
};
