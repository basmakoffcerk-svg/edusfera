<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users');
            $table->text('message');
            $table->timestamps();

            $table->index(['classroom_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_chat_messages');
    }
};
