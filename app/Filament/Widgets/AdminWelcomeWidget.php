<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Enums\UserRole;
use App\Models\Dispute;
use App\Models\Lesson;
use App\Models\NewsArticle;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdminWelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.admin-welcome-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = $user->role;

        return $role === UserRole::Admin || (is_object($role) ? $role->value : $role) === 'admin';
    }

    protected function getViewData(): array
    {
        $totalUsers = User::count();
        $totalTutors = User::where('role', 'tutor')->count();
        $totalStudents = User::where('role', 'student')->count();
        $usersThisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();

        $totalLessons = Lesson::count();
        $completedLessons = Lesson::where('status', Lesson::STATUS_COMPLETED)->count();
        $pendingRequests = Lesson::where('status', Lesson::STATUS_PENDING)->count();
        $confirmedLessons = Lesson::where('status', Lesson::STATUS_CONFIRMED)->where('start_time', '>=', now())->count();
        $completionRate = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        // SaaS Subscriptions metrics
        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE)->count();
        $trialSubscriptions = Subscription::where('status', SubscriptionStatus::TRIAL)->count();
        $totalSubscribers = Subscription::whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])->count();
        $founderSubscriptions = Subscription::where('is_founder', true)->count();

        // Calculate MRR from active subscriptions
        $mrr = 0.0;
        $activeSubs = Subscription::where('status', SubscriptionStatus::ACTIVE)->get();
        foreach ($activeSubs as $sub) {
            if ($sub->plan instanceof SubscriptionPlan) {
                $mrr += (float) $sub->plan->monthlyPriceByn();
            }
        }

        // Paid subscription revenue this month
        $subscriptionRevenueThisMonth = ((float) SubscriptionInvoice::where('status', InvoiceStatus::PAID)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount_kopecks')) / 100;

        // GMV: Total completed lesson volume + transactions
        $gmvTotal = (float) Lesson::where('status', Lesson::STATUS_COMPLETED)->sum('amount');
        if ($gmvTotal <= 0) {
            $gmvTotal = (float) Transaction::where('status', Transaction::STATUS_SUCCESS)->sum('amount');
        }

        $gmvThisMonth = (float) Lesson::where('status', Lesson::STATUS_COMPLETED)
            ->whereMonth('start_time', now()->month)
            ->whereYear('start_time', now()->year)
            ->sum('amount');
        if ($gmvThisMonth <= 0) {
            $gmvThisMonth = (float) Transaction::where('status', Transaction::STATUS_SUCCESS)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount');
        }

        $openDisputes = Dispute::where('status', Dispute::STATUS_OPEN)->count();
        $publishedArticles = NewsArticle::where('status', 'published')->count();
        $unpaidLessons = Lesson::where('payment_status', Lesson::PAYMENT_UNPAID)
            ->where('status', Lesson::STATUS_CONFIRMED)
            ->count();

        return [
            'adminName' => Auth::user()?->name ?? 'Администратор',
            'totalUsers' => $totalUsers,
            'totalTutors' => $totalTutors,
            'totalStudents' => $totalStudents,
            'usersThisWeek' => $usersThisWeek,
            'totalLessons' => $totalLessons,
            'completedLessons' => $completedLessons,
            'pendingRequests' => $pendingRequests,
            'confirmedLessons' => $confirmedLessons,
            'completionRate' => $completionRate,
            'activeSubscriptions' => $activeSubscriptions,
            'trialSubscriptions' => $trialSubscriptions,
            'totalSubscribers' => $totalSubscribers,
            'founderSubscriptions' => $founderSubscriptions,
            'mrr' => number_format($mrr, 2, '.', ' '),
            'subscriptionRevenueThisMonth' => number_format($subscriptionRevenueThisMonth, 2, '.', ' '),
            'gmvTotal' => number_format($gmvTotal, 2, '.', ' '),
            'gmvThisMonth' => number_format($gmvThisMonth, 2, '.', ' '),
            'openDisputes' => $openDisputes,
            'publishedArticles' => $publishedArticles,
            'unpaidLessons' => $unpaidLessons,
        ];
    }
}
