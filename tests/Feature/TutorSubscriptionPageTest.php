<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Filament\Pages\TutorSubscriptionPage;
use App\Filament\Widgets\TutorHeroWidget;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TutorSubscriptionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_access_subscription_page_and_see_plans(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => 'Анна Репетитор',
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->assertSuccessful()
            ->assertSee('«Старт»')
            ->assertSee('«Про»')
            ->assertSee('29')
            ->assertSee('59')
            ->assertSee('Неограниченно учеников')
            ->assertSee('Виртуальный класс (SFU)')
            ->assertSee('ИИ-диагностика знаний')
            ->assertSee('Авто-генерация чеков НПД')
            ->assertSee('Сформировать счёт в ЕРИП');
    }

    public function test_tutor_can_toggle_yearly_billing_and_change_plan(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        $component = Livewire::test(TutorSubscriptionPage::class)
            ->assertSet('isYearly', false)
            ->call('toggleYearly')
            ->assertSet('isYearly', true)
            ->call('changePlan', 'start');

        $subscription = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionPlan::START, $subscription->plan);

        // Check invoices created
        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $subscription->id,
            'plan' => 'start',
            'period_months' => 12,
            'amount_kopecks' => 29000,
        ]);
    }

    public function test_tutor_can_bind_card_and_activate_plan(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('subscribeWithCard', 'pro')
            ->assertSet('showCardModal', true)
            ->assertSet('selectedPlanCode', 'pro')
            ->call('confirmCardPayment')
            ->assertSet('showCardModal', false);

        $subscription = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals(SubscriptionPlan::PRO, $subscription->plan);

        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $subscription->id,
            'status' => 'paid',
            'payment_method' => 'card',
        ]);
    }

    public function test_tutor_can_cancel_and_resume_subscription(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);
        $sub = $service->startTrial($tutor, SubscriptionPlan::PRO);
        $sub->update(['status' => SubscriptionStatus::ACTIVE]);

        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('openCancelModal')
            ->assertSet('showCancelModal', true)
            ->call('cancelSubscription')
            ->assertSet('showCancelModal', false);

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::CANCELED, $sub->status);
        $this->assertNotNull($sub->canceled_at);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('resumeSubscription');

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertNull($sub->canceled_at);
    }

    public function test_tutor_hero_widget_shows_booking_link_and_subscription_pill(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => 'Елена Преподаватель',
        ]);

        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'is_verified' => true,
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorHeroWidget::class)
            ->assertSuccessful()
            ->assertSee('Ссылка для записи:')
            ->assertSee('Скопировать')
            ->assertSee('Пробный период')
            ->assertSee('/tutors/' . $profile->id);
    }

    public function test_tutor_hero_widget_shows_grace_period_alert_when_past_due(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);
        $sub = $service->startTrial($tutor, SubscriptionPlan::PRO);
        $sub->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'grace_period_ends_at' => now()->addDays(2),
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorHeroWidget::class)
            ->assertSuccessful()
            ->assertSee('Платёж за подписку не прошёл')
            ->assertSee('Обновить карту')
            ->assertSee('/admin/tutor-subscription-page');
    }
}
