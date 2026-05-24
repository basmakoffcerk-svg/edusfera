<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PackageService
{
    /**
     * @return array{code: string, lessons: int, total: float, discount: float}
     */
    public function resolve(string $packageCode, float $singleLessonPrice): array
    {
        return match ($packageCode) {
            'single' => [
                'code' => 'single',
                'lessons' => 1,
                'total' => round($singleLessonPrice, 2),
                'discount' => 0.0,
            ],
            'pack_4' => (function () use ($singleLessonPrice): array {
                $total = round($singleLessonPrice * 4 * 0.95, 2);

                return [
                    'code' => 'pack_4',
                    'lessons' => 4,
                    'total' => $total,
                    'discount' => round(($singleLessonPrice * 4) - $total, 2),
                ];
            })(),
            'pack_8' => (function () use ($singleLessonPrice): array {
                $total = round($singleLessonPrice * 8 * 0.90, 2);

                return [
                    'code' => 'pack_8',
                    'lessons' => 8,
                    'total' => $total,
                    'discount' => round(($singleLessonPrice * 8) - $total, 2),
                ];
            })(),
            default => throw ValidationException::withMessages([
                'package' => 'Выбран некорректный пакет оплаты.',
            ]),
        };
    }

    /**
     * Apply a package selection to a lesson and update its price fields.
     */
    public function applyToLesson(\App\Models\Lesson $lesson, string $packageCode): void
    {
        $singlePrice = (float) $lesson->price;
        $package = $this->resolve($packageCode, $singlePrice);
        $commission = round($package['total'] * (float) config('payments.commission_rate', 0.15), 2);
        $netAmount = round($package['total'] - $commission, 2);

        $lesson->update([
            'platform_commission' => number_format($commission, 2, '.', ''),
            'net_amount' => number_format($netAmount, 2, '.', ''),
            'package_code' => $package['code'],
            'package_lessons' => $package['lessons'],
            'package_lessons_remaining' => $package['lessons'],
            'package_total' => number_format($package['total'], 2, '.', ''),
            'package_discount' => number_format($package['discount'], 2, '.', ''),
        ]);
    }
}
