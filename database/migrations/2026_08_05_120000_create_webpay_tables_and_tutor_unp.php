<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webpay_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event')->nullable();
            $table->string('transaction_id')->nullable();
            $table->json('payload');
            $table->string('ip')->nullable();
            $table->integer('status_code')->default(200);
            $table->text('error_reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->string('unp', 9)->nullable()->after('legal_status')->comment('УНП репетитора для агентских чеков и сплит-выплат');
            $table->string('payout_account', 64)->nullable()->after('unp')->comment('Расчетный счет или номер карты репетитора для выплат');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webpay_webhook_logs');

        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropColumn(['unp', 'payout_account']);
        });
    }
};
