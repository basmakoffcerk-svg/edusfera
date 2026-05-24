<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->decimal('available_amount', 10, 2)->default(0);
            $table->decimal('pending_amount', 10, 2)->default(0);
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('total_withdrawn', 10, 2)->default(0);
            $table->timestamp('last_payout_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'tutor_balances_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_balances');
    }
};
