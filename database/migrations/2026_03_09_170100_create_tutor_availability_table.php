<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id', 'tutor_availability_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_availability');
    }
};
