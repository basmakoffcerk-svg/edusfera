<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Subscription\Services\SubscriptionService;
use App\Services\Payment\AlfaBankPaymentGateway;
use Illuminate\Console\Command;

class ProcessSubscriptionBillingCommand extends Command
{
    protected $signature = 'edusfera:subscriptions-process-billing';

    protected $description = 'Process daily SaaS subscription billing cycle (auto-charges, grace periods, deactivations)';

    public function handle(SubscriptionService $service, AlfaBankPaymentGateway $gateway): int
    {
        $this->info('Starting SaaS subscription billing cycle check...');

        $stats = $service->processDailyBillingCycle($gateway);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Renewed / Charged Automatically', $stats['charged'] ?? 0],
                ['Invoices Created', $stats['invoices_created'] ?? 0],
                ['Moved to Grace Period (Overdue / Failed)', $stats['moved_to_grace'] ?? 0],
                ['Deactivated Subscriptions (Expired)', $stats['expired'] ?? 0],
            ]
        );

        $this->info('SaaS subscription billing check completed successfully.');

        return self::SUCCESS;
    }
}
