<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Lesson;
use App\Models\User;

class AgentFiscalReceiptService
{
    private string $platformUnp;
    private string $platformName;

    public function __construct()
    {
        $this->platformUnp = (string) config('payments.webpay.platform_unp', '192854899');
        $this->platformName = 'ООО «Эдусфера»';
    }

    /**
     * Формирует структуру агентского фискального чека для WebPAY.
     */
    public function generateReceiptPayload(Lesson $lesson, float $amount, User $studentOrParent): array
    {
        $lesson->loadMissing(['tutor.tutorProfile']);
        $tutor = $lesson->tutor;
        $tutorProfile = $tutor?->tutorProfile;

        $principalTransit = round($amount, 2);

        $principalUnp = $tutorProfile?->unp ?? '000000000';
        $principalName = $tutor?->name ?? 'Репетитор-принципал';

        return [
            'receipt' => [
                'agent_info' => [
                    'is_agent' => true,
                    'agent_type' => 'agent',
                    'agent_name' => $this->platformName,
                    'agent_unp' => $this->platformUnp,
                ],
                'principal_info' => [
                    'principal_unp' => $principalUnp,
                    'principal_name' => $principalName,
                ],
                'customer' => [
                    'name' => $studentOrParent->name,
                    'email' => $studentOrParent->email,
                ],
                'items' => [
                    [
                        'name' => 'Обучающие занятия по предмету (Репетиторство)',
                        'price' => number_format($principalTransit, 2, '.', ''),
                        'quantity' => 1,
                        'amount' => number_format($principalTransit, 2, '.', ''),
                        'vat_rate' => 'NO_VAT',
                        'item_type' => 'principal_service',
                    ],
                ],
                'total_amount' => number_format($amount, 2, '.', ''),
                'currency' => config('payments.currency', 'BYN'),
            ],
        ];
    }
}
