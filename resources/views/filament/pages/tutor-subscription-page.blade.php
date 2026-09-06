<x-filament-panels::page>
    <style>
        .tutor-sub-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
            font-family: inherit;
        }

        /* ═══ Header & Status Banners ═════════════════════════════════ */
        .tutor-sub-hero {
            position: relative;
            background: linear-gradient(135deg, #0C0A14 0%, #1F1B2E 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 28px 32px;
            color: #FFFFFF;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(12, 10, 20, 0.5);
        }

        .tutor-sub-hero-badge-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .tutor-sub-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            line-height: 1;
        }

        .tutor-sub-badge--emerald {
            background: #059669;
            color: #FFFFFF;
            box-shadow: 0 0 12px rgba(5, 150, 105, 0.4);
        }

        .tutor-sub-badge--purple {
            background: #7D39EB;
            color: #FFFFFF;
        }

        .tutor-sub-badge--founder {
            background: #F59E0B;
            color: #000000;
            font-weight: 900;
        }

        .tutor-sub-badge--outline {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #E5E7EB;
        }

        .tutor-sub-hero-title {
            margin: 0 0 8px 0;
            font-size: clamp(1.6rem, 1.2rem + 1.8vw, 2.35rem);
            font-weight: 900;
            letter-spacing: -0.025em;
            line-height: 1.15;
            color: #FFFFFF;
        }

        .tutor-sub-hero-subtitle {
            margin: 0;
            font-size: 0.9375rem;
            line-height: 1.5;
            color: #9CA3AF;
            max-width: 680px;
        }

        /* Grace Period Red/Amber Alert */
        .tutor-sub-alert-grace {
            background: #FEF2F2;
            border: 1.5px solid #F87171;
            border-radius: 18px;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            color: #991B1B;
        }
        .dark .tutor-sub-alert-grace {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.35);
            color: #FCA5A5;
        }

        .tutor-sub-alert-icon {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            color: #DC2626;
        }
        .dark .tutor-sub-alert-icon {
            color: #F87171;
        }

        /* ═══ Billing Cycle Toggle ═══════════════════════════════════ */
        .tutor-sub-toggle-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 8px 0;
            text-align: center;
        }

        .tutor-sub-toggle-pill {
            display: inline-flex;
            align-items: center;
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
            border-radius: 9999px;
            padding: 4px;
            gap: 4px;
            user-select: none;
            cursor: pointer;
        }
        .dark .tutor-sub-toggle-pill {
            background: #1F2937;
            border-color: #374151;
        }

        .tutor-sub-toggle-btn {
            padding: 8px 20px;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s ease;
            color: #6B7280;
            border: none;
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .dark .tutor-sub-toggle-btn {
            color: #9CA3AF;
        }

        .tutor-sub-toggle-btn.is-active {
            background: #FFFFFF;
            color: #0C0A14;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }
        .dark .tutor-sub-toggle-btn.is-active {
            background: #111827;
            color: #FFFFFF;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .tutor-sub-discount-tag {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            background: #059669;
            color: #FFFFFF;
        }

        /* ═══ Pricing Grid ═══════════════════════════════════════════ */
        .tutor-sub-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        @media (min-width: 768px) {
            .tutor-sub-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .tutor-plan-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 24px;
            background: #FFFFFF;
            border: 1.5px solid #E5E7EB;
            padding: 32px;
            position: relative;
            transition: transform 0.15s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .dark .tutor-plan-card {
            background: #111827;
            border-color: #1F2937;
        }
        .tutor-plan-card:hover {
            border-color: #CBD5E1;
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.06);
        }
        .dark .tutor-plan-card:hover {
            border-color: #374151;
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.3);
        }

        .tutor-plan-card--pro {
            border: 2px solid #059669;
            background: #FAFDFB;
        }
        .dark .tutor-plan-card--pro {
            border-color: #059669;
            background: rgba(5, 150, 105, 0.05);
        }

        .tutor-plan-badge-hit {
            position: absolute;
            top: -13px;
            right: 28px;
            background: #059669;
            color: #FFFFFF;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 14px;
            border-radius: 9999px;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);
        }

        .tutor-plan-header {
            margin-bottom: 24px;
        }

        .tutor-plan-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .tutor-plan-name {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            margin: 0;
            color: #0C0A14;
        }
        .dark .tutor-plan-name {
            color: #FFFFFF;
        }

        .tutor-plan-tag-current {
            background: #E5E7EB;
            color: #374151;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 9999px;
        }
        .dark .tutor-plan-tag-current {
            background: #374151;
            color: #E5E7EB;
        }

        .tutor-plan-price-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 12px;
        }

        .tutor-plan-price {
            font-size: 2.75rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1;
            color: #0C0A14;
            font-variant-numeric: tabular-nums;
        }
        .dark .tutor-plan-price {
            color: #FFFFFF;
        }

        .tutor-plan-price--pro {
            color: #059669;
        }
        .dark .tutor-plan-price--pro {
            color: #10B981;
        }

        .tutor-plan-unit {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #6B7280;
        }
        .dark .tutor-plan-unit {
            color: #9CA3AF;
        }

        .tutor-plan-sub-billing {
            margin-top: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #059669;
        }
        .dark .tutor-plan-sub-billing {
            color: #34D399;
        }

        /* Features List */
        .tutor-features-list {
            list-style: none;
            padding: 0;
            margin: 24px 0 32px 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .tutor-feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.875rem;
            line-height: 1.45;
            color: #374151;
        }
        .dark .tutor-feature-item {
            color: #D1D5DB;
        }

        .tutor-feature-check {
            width: 20px;
            height: 20px;
            border-radius: 9999px;
            background: rgba(5, 150, 105, 0.12);
            color: #059669;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 1px;
        }
        .dark .tutor-feature-check {
            background: rgba(5, 150, 105, 0.25);
            color: #34D399;
        }

        /* Plan Action Button */
        .tutor-plan-btn {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            font-size: 0.9375rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            border: none;
        }
        .tutor-plan-btn:active {
            transform: scale(0.98);
        }

        .tutor-plan-btn--primary {
            background: #059669;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }
        .tutor-plan-btn--primary:hover {
            background: #047857;
        }

        .tutor-plan-btn--outline {
            background: transparent;
            border: 1.5px solid #0C0A14;
            color: #0C0A14;
        }
        .dark .tutor-plan-btn--outline {
            border-color: #4B5563;
            color: #FFFFFF;
        }
        .tutor-plan-btn--outline:hover {
            background: #F3F4F6;
        }
        .dark .tutor-plan-btn--outline:hover {
            background: #1F2937;
        }

        .tutor-plan-btn--disabled {
            background: #F3F4F6;
            color: #9CA3AF;
            border: 1px solid #E5E7EB;
            cursor: not-allowed;
        }
        .dark .tutor-plan-btn--disabled {
            background: #1F2937;
            color: #6B7280;
            border-color: #374151;
        }

        /* ═══ Payment Guarantee & Alfa Badge ═══════════════════════════ */
        .tutor-sub-guarantee-box {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 24px;
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 18px;
        }
        .dark .tutor-sub-guarantee-box {
            background: #111827;
            border-color: #1F2937;
        }

        .tutor-sub-guarantee-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tutor-sub-guarantee-icon {
            width: 32px;
            height: 32px;
            color: #059669;
            flex-shrink: 0;
        }

        /* ═══ Invoice & Action Hub ════════════════════════════════════ */
        .tutor-card-section {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 24px;
            padding: 28px 32px;
        }
        .dark .tutor-card-section {
            background: #111827;
            border-color: #1F2937;
        }

        .tutor-card-section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .tutor-card-section-title {
            font-size: 1.125rem;
            font-weight: 800;
            color: #0C0A14;
            margin: 0;
        }
        .dark .tutor-card-section-title {
            color: #FFFFFF;
        }

        /* Table styles */
        .tutor-invoices-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            text-align: left;
        }
        .tutor-invoices-table th {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6B7280;
            border-bottom: 1px solid #E5E7EB;
            background: #F9FAFB;
        }
        .dark .tutor-invoices-table th {
            color: #9CA3AF;
            border-color: #1F2937;
            background: rgba(31, 41, 55, 0.4);
        }
        .tutor-invoices-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #F3F4F6;
            color: #1F2937;
        }
        .dark .tutor-invoices-table td {
            border-color: #1F2937;
            color: #E5E7EB;
        }

        /* Modal Backdrop */
        .tutor-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(12, 10, 20, 0.7);
            backdrop-filter: blur(4px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .tutor-modal-card {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 24px;
            padding: 32px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.3);
        }
        .dark .tutor-modal-card {
            background: #111827;
            border-color: #1F2937;
        }
    </style>

    <div class="tutor-sub-container" x-data="{ isYearly: @entangle('isYearly') }">

        @if($subscription)
            {{-- 1. Grace Period Alert (If applicable) --}}
            @if($subscription->isInGracePeriod())
                <div class="tutor-sub-alert-grace">
                    <svg class="tutor-sub-alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: 800;">Платеж не прошёл</h4>
                        <p style="margin: 0; font-size: 0.875rem; line-height: 1.4;">
                            Действует льготный период (осталось <strong>{{ $subscription->graceDaysRemaining() }} {{ trans_choice('день|дня|дней', $subscription->graceDaysRemaining()) }}</strong>). Пожалуйста, обновите карту во избежание блокировки виртуального класса.
                        </p>
                        <div style="margin-top: 14px;">
                            <button wire:click="subscribeWithCard('{{ $subscription->plan->value }}')" type="button" class="tutor-plan-btn tutor-plan-btn--primary" style="width: auto; height: 38px; padding: 0 18px; font-size: 0.8125rem;">
                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75v10.5A2.25 2.25 0 0 0 4.5 21Z"/></svg>
                                Привязать новую карту (Альфа-Банк)
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 2. Hero Overview & Current Status Badge --}}
            <div class="tutor-sub-hero">
                <div class="tutor-sub-hero-badge-row">
                    {{-- Status Badge --}}
                    @if($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::TRIAL)
                        <span class="tutor-sub-badge tutor-sub-badge--emerald">
                            <span style="width: 6px; height: 6px; border-radius: 9999px; background: #FFFFFF;"></span>
                            Бесплатный ознакомительный период (Осталось {{ $subscription->daysRemaining() }} {{ trans_choice('день|дня|дней', $subscription->daysRemaining()) }})
                        </span>
                    @elseif($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                        <span class="tutor-sub-badge tutor-sub-badge--emerald">
                            ✓ Активная подписка: «{{ $subscription->plan->title() }}», действует до {{ $subscription->current_period_ends_at?->format('d.m.Y') }}
                        </span>
                    @elseif($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::CANCELED)
                        <span class="tutor-sub-badge tutor-sub-badge--outline">
                            Отменена (доступна до {{ $subscription->current_period_ends_at?->format('d.m.Y') }})
                        </span>
                    @else
                        <span class="tutor-sub-badge tutor-sub-badge--outline">
                            {{ $subscription->status->title() }}
                        </span>
                    @endif

                    @if($subscription->is_founder)
                        <span class="tutor-sub-badge tutor-sub-badge--founder">
                            👑 Основатель Edusfera
                        </span>
                    @endif
                </div>

                <h1 class="tutor-sub-hero-title">
                    @if($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::TRIAL)
                        Ознакомительный период на тарифе «{{ $subscription->plan->title() }}»
                    @elseif($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                        Ваш тариф: «{{ $subscription->plan->title() }}»
                    @else
                        Управление тарифным планом
                    @endif
                </h1>

                <p class="tutor-sub-hero-subtitle">
                    Неограниченное число учеников, защищённый видеокласс SFU и автоматизация расписания. Выберите подходящий план для масштабирования частной практики.
                </p>

                <div style="margin-top: 24px; display: flex; flex-wrap: wrap; align-items: center; gap: 12px;">
                    <button wire:click="createNewInvoice" type="button" class="tutor-plan-btn tutor-plan-btn--outline" style="width: auto; height: 40px; padding: 0 20px; color: #FFFFFF; border-color: rgba(255, 255, 255, 0.2); font-size: 0.875rem;">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        Сформировать счёт в ЕРИП
                    </button>

                    @if($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                        <button wire:click="openCancelModal" type="button" style="background: transparent; border: none; color: #9CA3AF; font-size: 0.8125rem; cursor: pointer; text-decoration: underline; padding: 6px 12px;">
                            Отменить подписку
                        </button>
                    @elseif($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::CANCELED)
                        <button wire:click="resumeSubscription" type="button" style="background: transparent; border: 1px solid #059669; color: #34D399; border-radius: 12px; font-size: 0.8125rem; font-weight: 700; cursor: pointer; padding: 8px 16px;">
                            Возобновить подписку
                        </button>
                    @endif
                </div>
            </div>

            {{-- 3. Interactive Monthly / Yearly Toggle --}}
            <div class="tutor-sub-toggle-wrapper">
                <div class="tutor-sub-toggle-pill" @click="isYearly = !isYearly; $wire.toggleYearly()">
                    <button type="button" class="tutor-sub-toggle-btn" :class="{ 'is-active': !isYearly }">
                        Ежемесячно
                    </button>
                    <button type="button" class="tutor-sub-toggle-btn" :class="{ 'is-active': isYearly }">
                        <span>На 1 год</span>
                        <span class="tutor-sub-discount-tag">-20% выгода</span>
                    </button>
                </div>
                <p style="margin: 0; font-size: 0.8125rem; color: #6B7280;">
                    <span x-show="isYearly">Оплата один раз в год · 2 месяца в подарок</span>
                    <span x-show="!isYearly">Списание каждый месяц · Отмена в любой момент</span>
                </p>
            </div>

            {{-- 4. Plans Comparison Grid --}}
            <div class="tutor-sub-grid">

                {{-- PLAN 1: «Старт» --}}
                @php
                    $isCurrentStart = in_array($subscription->plan, [\App\Domain\Subscription\Enums\SubscriptionPlan::START, \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC], true);
                @endphp
                <div class="tutor-plan-card">
                    <div>
                        <div class="tutor-plan-header">
                            <div class="tutor-plan-title-row">
                                <h3 class="tutor-plan-name">«Старт»</h3>
                                @if($isCurrentStart)
                                    <span class="tutor-plan-tag-current">Текущий тариф</span>
                                @endif
                            </div>
                            <p style="margin: 0; font-size: 0.875rem; color: #6B7280;">
                                Для независимых преподавателей и комфортного ведения уроков.
                            </p>

                            <div class="tutor-plan-price-row">
                                <span class="tutor-plan-price" x-text="isYearly ? '24.16' : '29'">29</span>
                                <span class="tutor-plan-unit">BYN / мес</span>
                            </div>
                            <div class="tutor-plan-sub-billing" x-show="isYearly">
                                290 BYN в год (вместо 348 BYN)
                            </div>
                        </div>

                        <ul class="tutor-features-list">
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Неограниченно учеников</strong> и карточек контактов</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Виртуальный класс (SFU)</strong> с видеосвязью и демонстрацией экрана</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Интерактивная доска</strong> для формул и заметок</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Расписание и CRM</strong>: бронирование и контроль посещаемости</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Персональная ссылка для записи</strong> учеников</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        @if($isCurrentStart && $subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                            <button type="button" disabled class="tutor-plan-btn tutor-plan-btn--disabled">
                                Тариф подключён
                            </button>
                        @else
                            <button 
                                wire:click="subscribeWithCard('start')" 
                                wire:loading.attr="disabled"
                                type="button" 
                                class="tutor-plan-btn tutor-plan-btn--outline"
                            >
                                <span wire:loading.remove wire:target="subscribeWithCard('start')">
                                    {{ $isCurrentStart ? 'Привязать карту и продлить' : 'Подключить «Старт»' }}
                                </span>
                                <span wire:loading wire:target="subscribeWithCard('start')">Связь с банком...</span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- PLAN 2: «Про» (Hero Card) --}}
                @php
                    $isCurrentPro = in_array($subscription->plan, [\App\Domain\Subscription\Enums\SubscriptionPlan::PRO, \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM], true);
                @endphp
                <div class="tutor-plan-card tutor-plan-card--pro">
                    <span class="tutor-plan-badge-hit">★ Рекомендуемый</span>

                    <div>
                        <div class="tutor-plan-header">
                            <div class="tutor-plan-title-row">
                                <h3 class="tutor-plan-name" style="color: #059669;">«Про»</h3>
                                @if($isCurrentPro)
                                    <span class="tutor-plan-tag-current" style="background: rgba(5, 150, 105, 0.15); color: #059669;">Текущий тариф</span>
                                @endif
                            </div>
                            <p style="margin: 0; font-size: 0.875rem; color: #6B7280;">
                                Максимум автоматизации: ИИ-помощник, диагностика и авто-чеки НПД.
                            </p>

                            <div class="tutor-plan-price-row">
                                <span class="tutor-plan-price tutor-plan-price--pro" x-text="isYearly ? '49.16' : '59'">59</span>
                                <span class="tutor-plan-unit">BYN / мес</span>
                            </div>
                            <div class="tutor-plan-sub-billing" x-show="isYearly">
                                590 BYN в год (вместо 708 BYN)
                            </div>
                        </div>

                        <ul class="tutor-features-list">
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Все функции тарифа «Старт»</strong></span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>ИИ-диагностика знаний (РИКЗ)</strong> для оценки пробелов ученика</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>ИИ-ассистент</strong>: автоматическая генерация планов уроков и тестов</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Авто-генерация чеков НПД</strong> для МНС РБ (налог на проф. доход)</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Персональный брендинг</strong> комнат и приоритетное размещение</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        @if($isCurrentPro && $subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                            <button type="button" disabled class="tutor-plan-btn tutor-plan-btn--disabled">
                                Тариф подключён
                            </button>
                        @else
                            <button 
                                wire:click="subscribeWithCard('pro')" 
                                wire:loading.attr="disabled"
                                type="button" 
                                class="tutor-plan-btn tutor-plan-btn--primary"
                            >
                                <span wire:loading.remove wire:target="subscribeWithCard('pro')">
                                    {{ $isCurrentPro ? 'Привязать карту и продлить' : 'Подключить «Про»' }}
                                </span>
                                <span wire:loading wire:target="subscribeWithCard('pro')">Связь с банком...</span>
                            </button>
                        @endif
                    </div>
                </div>

            </div>

            {{-- 5. Safe Payment Guarantee Box --}}
            <div class="tutor-sub-guarantee-box">
                <div class="tutor-sub-guarantee-left">
                    <svg class="tutor-sub-guarantee-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    <div>
                        <div style="font-size: 0.9375rem; font-weight: 800; color: #0C0A14;" class="dark:text-white">Безопасная оплата через Альфа-Банк</div>
                        <div style="font-size: 0.8125rem; color: #6B7280;">Протокол 3-D Secure 2.0. Карты Visa, Mastercard, БЕЛКАРТ, МИР. Данные карты не хранятся на сервере Edusfera.</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 8px; background: #E5E7EB; color: #374151;">PCI DSS Level 1</span>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 8px; background: #DC2626; color: #FFFFFF;">Альфа-Банк</span>
                </div>
            </div>

            {{-- 6. ERIP Information Section --}}
            <div class="tutor-card-section">
                <div class="tutor-card-section-head">
                    <div>
                        <h3 class="tutor-card-section-title">Оплата подписки через ЕРИП (АИС «Расчёт»)</h3>
                        <p style="margin: 4px 0 0 0; font-size: 0.8125rem; color: #6B7280;">
                            Прямой платёж без привязки банковской карты через любой интернет-банкинг или инфокиоск Беларуси.
                        </p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-top: 16px;">
                    <div style="padding: 16px; border-radius: 16px; background: #F9FAFB; border: 1px solid #E5E7EB;" class="dark:bg-gray-800 dark:border-gray-700">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #9CA3AF;">1. Путь в дереве ЕРИП</div>
                        <div style="font-size: 0.875rem; font-weight: 700; margin-top: 6px; color: #111827;" class="dark:text-white">
                            Образование и развитие → Информационные услуги → Эдусфера
                        </div>
                    </div>

                    <div style="padding: 16px; border-radius: 16px; background: #F9FAFB; border: 1px solid #E5E7EB;" class="dark:bg-gray-800 dark:border-gray-700">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #9CA3AF;">2. Ваш номер абонента (л/с)</div>
                        <div style="font-size: 1.125rem; font-family: monospace; font-weight: 900; margin-top: 6px; color: #059669;">
                            EDU{{ sprintf('%05d', auth()->id()) }}
                        </div>
                    </div>

                    <div style="padding: 16px; border-radius: 16px; background: #F9FAFB; border: 1px solid #E5E7EB;" class="dark:bg-gray-800 dark:border-gray-700">
                        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #9CA3AF;">3. Сумма к оплате</div>
                        <div style="font-size: 1.125rem; font-weight: 900; margin-top: 6px; color: #111827;" class="dark:text-white">
                            {{ $subscription->plan->monthlyPriceByn() }} BYN / мес
                        </div>
                    </div>
                </div>
            </div>

            {{-- 7. Invoices & Billing History Table --}}
            <div class="tutor-card-section">
                <div class="tutor-card-section-head">
                    <div>
                        <h3 class="tutor-card-section-title">История счетов и оплат</h3>
                        <p style="margin: 4px 0 0 0; font-size: 0.8125rem; color: #6B7280;">
                            Квитанции и акты для налогового учёта (НПД).
                        </p>
                    </div>
                </div>

                <div style="overflow-x: auto; margin-top: 12px;">
                    <table class="tutor-invoices-table">
                        <thead>
                            <tr>
                                <th>Номер счёта</th>
                                <th>Тарифный план</th>
                                <th>Период</th>
                                <th>Сумма</th>
                                <th>Срок оплаты</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td style="font-family: monospace; font-weight: 700;">{{ $invoice->invoice_number }}</td>
                                    <td style="font-weight: 700;">{{ $invoice->plan->title() }}</td>
                                    <td>{{ $invoice->period_months === 12 ? '1 год (-20%)' : '1 месяц' }}</td>
                                    <td style="font-weight: 800;">{{ $invoice->amountByn() }} BYN</td>
                                    <td style="color: #6B7280;">{{ $invoice->due_date?->format('d.m.Y') ?? '—' }}</td>
                                    <td>
                                        @if($invoice->status === \App\Domain\Subscription\Enums\InvoiceStatus::PAID)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(5, 150, 105, 0.12); color: #059669; font-size: 11px; font-weight: 800;">
                                                ✓ Оплачен
                                            </span>
                                        @elseif($invoice->status === \App\Domain\Subscription\Enums\InvoiceStatus::PENDING)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(245, 158, 11, 0.12); color: #D97706; font-size: 11px; font-weight: 800;">
                                                Ожидает оплаты
                                            </span>
                                        @else
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(107, 114, 128, 0.12); color: #6B7280; font-size: 11px; font-weight: 800;">
                                                {{ $invoice->status->title() }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 32px 16px; color: #9CA3AF;">
                                        Счетов пока нет. Бесплатный ознакомительный период действует автоматически.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 8. Card Binding Modal (Alfa-Bank) --}}
            @if($showCardModal)
                <div class="tutor-modal-overlay">
                    <div class="tutor-modal-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: #DC2626; color: #FFFFFF; font-weight: 900; display: flex; align-items: center; justify-content: center; font-size: 14px;">А</div>
                                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0C0A14;" class="dark:text-white">Привязка карты</h3>
                            </div>
                            <button wire:click="closeCardModal" type="button" style="background: transparent; border: none; cursor: pointer; color: #9CA3AF; font-size: 20px;">✕</button>
                        </div>

                        <p style="font-size: 0.875rem; color: #6B7280; line-height: 1.5; margin: 0 0 20px 0;">
                            Шлюз Альфа-Банка готов к авторизации карты для тарифа <strong>«{{ $selectedPlanCode === 'start' ? 'Старт' : 'Про' }}»</strong>.
                        </p>

                        <div style="padding: 16px; border-radius: 14px; background: #F9FAFB; border: 1px solid #E5E7EB; margin-bottom: 24px;" class="dark:bg-gray-800 dark:border-gray-700">
                            <div style="font-size: 12px; color: #6B7280;">Идентификатор заказа в банке:</div>
                            <div style="font-family: monospace; font-size: 0.875rem; font-weight: 700; color: #111827; margin-top: 4px;" class="dark:text-white">
                                {{ $alfaOrderId ?? 'ALFA-' . strtoupper(bin2hex(random_bytes(4))) }}
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <button wire:click="confirmCardPayment" type="button" class="tutor-plan-btn tutor-plan-btn--primary">
                                Подтвердить привязку карты
                            </button>
                            <button wire:click="closeCardModal" type="button" class="tutor-plan-btn tutor-plan-btn--outline">
                                Отмена
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 9. Cancel Subscription Confirmation Modal --}}
            @if($showCancelModal)
                <div class="tutor-modal-overlay">
                    <div class="tutor-modal-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #991B1B;">Отмена подписки</h3>
                            <button wire:click="closeCancelModal" type="button" style="background: transparent; border: none; cursor: pointer; color: #9CA3AF; font-size: 20px;">✕</button>
                        </div>

                        <p style="font-size: 0.875rem; color: #4B5563; line-height: 1.5; margin: 0 0 24px 0;" class="dark:text-gray-300">
                            Вы действительно хотите отменить автопродление подписки? Все возможности тарифа останутся доступны до конца текущего оплаченного периода (<strong>{{ $subscription->current_period_ends_at?->format('d.m.Y') ?? 'окончания тарифа' }}</strong>).
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <button wire:click="cancelSubscription" type="button" class="tutor-plan-btn" style="background: #DC2626; color: #FFFFFF;">
                                Подтвердить отмену
                            </button>
                            <button wire:click="closeCancelModal" type="button" class="tutor-plan-btn tutor-plan-btn--outline">
                                Оставить подписку
                            </button>
                        </div>
                    </div>
                </div>
            @endif

        @endif

    </div>
</x-filament-panels::page>
