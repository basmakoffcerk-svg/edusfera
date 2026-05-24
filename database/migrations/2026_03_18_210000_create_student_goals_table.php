<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 120);
            $table->string('exam_type', 32);
            $table->unsignedSmallInteger('current_score')->nullable();
            $table->unsignedSmallInteger('target_score')->nullable();
            $table->date('exam_date')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('latest_diagnostic_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['subject', 'exam_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_goals');
    }
};
