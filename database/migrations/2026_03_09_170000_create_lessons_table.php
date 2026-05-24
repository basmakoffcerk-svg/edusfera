<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->index();
            $table->foreignId('student_id')->index();
            $table->foreignId('parent_id')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->decimal('price', 10, 2);
            $table->decimal('platform_commission', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'partially_refunded'])->default('unpaid');
            $table->string('meeting_link')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tutor_id', 'lessons_tutor_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('student_id', 'lessons_student_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('parent_id', 'lessons_parent_id_foreign')->references('id')->on('users')->nullOnDelete();
            $table->index(['tutor_id', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
