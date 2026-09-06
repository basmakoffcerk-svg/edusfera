<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Subscription\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptionBillingCommand extends Command
{
    protected $signature = 'edusfera:subscriptions-process-billing';

    protected $description = 'Process daily SaaS subscription billing cycle (T-3 invoices, grace periods, deactivations)';

    public function handle(SubscriptionService $service): int
    {
        $this->info('Starting SaaS subscription billing cycle check...');

        $stats = $service->processDailyBillingCycle();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Invoices Created (T-3 / Renewals)', $stats['invoices_created']],
                ['Moved to Grace Period (Overdue)', $stats['moved_to_grace']],
                ['Deactivated Subscriptions (Expired)', $stats['expired']],
            ]
        );

        $this->info('SaaS subscription billing check completed successfully.');

        return self::SUCCESS;
    }
}
