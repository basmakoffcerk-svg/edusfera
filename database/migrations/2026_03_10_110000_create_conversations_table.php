<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->nullable();
            $table->foreignId('tutor_id');
            $table->foreignId('student_id');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('lesson_id', 'conversations_lesson_id_foreign')->references('id')->on('lessons')->nullOnDelete();
            $table->foreign('tutor_id', 'conversations_tutor_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('student_id', 'conversations_student_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['tutor_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
