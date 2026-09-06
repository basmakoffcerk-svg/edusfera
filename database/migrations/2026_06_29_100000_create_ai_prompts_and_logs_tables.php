<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('system_prompt');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_prompt_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model');
            $table->text('prompt');
            $table->text('response')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->decimal('cost', 8, 4)->default(0.0000);
            $table->boolean('is_error')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_logs');
        Schema::dropIfExists('ai_prompts');
    }
};
