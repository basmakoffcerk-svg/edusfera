<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->string('webpay_billing_id', 64)->nullable()->after('payout_account')->comment('Billing ID репетитора в WebPAY');
            $table->string('webpay_account_id', 64)->nullable()->after('webpay_billing_id')->comment('Account ID репетитора в WebPAY');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropColumn(['webpay_billing_id', 'webpay_account_id']);
        });
    }
};
