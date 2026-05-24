<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_balance_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_balance_id')->constrained('student_balances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BYN');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['lesson_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_balance_ledger_entries');
    }
};
