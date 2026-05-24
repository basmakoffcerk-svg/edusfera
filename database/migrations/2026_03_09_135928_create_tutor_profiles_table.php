<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tutor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->json('subjects')->nullable();
            $table->decimal('price_per_hour', 10, 2)->default(0);
            $table->integer('experience_years')->default(0);
            $table->enum('legal_status', ['npd', 'ip', 'self_employed', 'none'])->default('none');
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutor_profiles');
    }
};
