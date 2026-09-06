<x-filament-panels::page>

<style>
    /* ═══ EDUSFERA FINANCIAL LEDGER (УЧЁТ ДЕНЕГ) 2026 ═══ */
    .ed-wallet {
        --ed-primary: #7D39EB;
        --ed-primary-hover: #6827D6;
        --ed-primary-light: rgba(125, 57, 235, 0.06);
        --ed-lime: #C6FF33;
        --ed-dark: #0F1117;
        --ed-dark-card: #161822;
        --ed-muted: #64748B;
        --ed-border: rgba(15, 17, 23, 0.08);
        --ed-radius: 1.25rem;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    /* ── Info Banner ── */
    .ed-w-banner {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.08) 0%, rgba(198, 255, 51, 0.12) 100%);
        border: 1px solid rgba(125, 57, 235, 0.2);
        margin-bottom: 2rem;
    }

    .ed-w-banner-icon {
        font-size: 1.75rem;
        line-height: 1;
        flex-shrink: 0;
    }

    .ed-w-banner strong {
        display: block;
        font-size: 1rem;
        font-weight: 800;
        color: var(--ed-dark);
        margin-bottom: 0.25rem;
    }

    .ed-w-banner p {
        margin: 0;
        font-size: 0.88rem;
        color: #475569;
        line-height: 1.5;
    }

    /* ── Hero Accounting Cards ── */
    .ed-w-hero {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .ed-w-hero-card {
        position: relative;
        padding: 1.75rem 2rem;
        border-radius: 1.5rem;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px;
        box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.05);
        transition: transform 0.25s ease;
    }

    .ed-w-hero-card:hover {
        transform: translateY(-2px);
    }

    .ed-w-hero-card--spent {
        background: linear-gradient(145deg, #0F1117 0%, #1A1D2A 100%);
        color: #FFFFFF;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .ed-w-hero-card--refunded {
        background: linear-gradient(145deg, #181E29 0%, #0F141C 100%);
        color: #FFFFFF;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .ed-w-hero-card--safe {
        background: linear-gradient(145deg, #1E1B4B 0%, #311042 100%);
        color: #FFFFFF;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .ed-w-hero-glow {
        position: absolute;
        right: -30px;
        top: -30px;
        width: 130px;
        height: 130px;
        border-radius: 50%;
        filter: blur(40px);
        pointer-events: none;
    }

    .ed-w-hero-card--spent .ed-w-hero-glow { background: rgba(198, 255, 51, 0.25); }
    .ed-w-hero-card--refunded .ed-w-hero-glow { background: rgba(56, 189, 248, 0.25); }
    .ed-w-hero-card--safe .ed-w-hero-glow { background: rgba(168, 85, 247, 0.3); }

    .ed-w-hero-label {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .ed-w-hero-card--spent .ed-w-hero-label { color: var(--ed-lime); }
    .ed-w-hero-card--refunded .ed-w-hero-label { color: #38BDF8; }
    .ed-w-hero-card--safe .ed-w-hero-label { color: #C084FC; }

    .ed-w-hero-amount {
        margin-top: 0.75rem;
        font-size: clamp(1.8rem, 3.5vw, 2.5rem);
        font-weight: 900;
        letter-spacing: -0.04em;
        line-height: 1;
    }

    .ed-w-hero-hint {
        margin-top: 0.65rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.65);
    }

    /* ── Main Section Card ── */
    .ed-w-section {
        background: #FFFFFF;
        border: 1px solid var(--ed-border);
        border-radius: 1.5rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.03);
    }

    .ed-w-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .ed-w-section-title {
        font-size: 1.3rem;
        font-weight: 900;
        letter-spacing: -0.03em;
        color: var(--ed-dark);
        margin: 0;
    }

    .ed-w-section-subtitle {
        font-size: 0.85rem;
        color: var(--ed-muted);
        margin-top: 0.2rem;
    }

    .ed-w-pay-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        background: #F1F5F9;
        color: #475569;
    }

    /* ── Transaction History Table ── */
    .ed-w-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .ed-w-table th {
        padding: 0.9rem 1.15rem;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94A3B8;
        text-align: left;
        border-bottom: 2px solid #F1F5F9;
    }

    .ed-w-table th:last-child {
        text-align: right;
    }

    .ed-w-table td {
        padding: 1rem 1.15rem;
        font-size: 0.88rem;
        vertical-align: middle;
        border-bottom: 1px solid #F8FAFC;
    }

    .ed-w-table tbody tr:hover {
        background: #F8FAFC;
    }

    .ed-w-table td:last-child {
        text-align: right;
        color: #94A3B8;
        font-weight: 600;
        font-size: 0.82rem;
    }

    .ed-w-tx-amount {
        font-weight: 900;
        color: var(--ed-dark);
    }

    .ed-w-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .ed-w-chip--card { background: #DCFCE7; color: #15803D; }
    .ed-w-chip--pending { background: #FEF3C7; color: #B45309; }
    .ed-w-chip--failed { background: #FEE2E2; color: #B91C1C; }
    .ed-w-chip--refund { background: #F3E8FF; color: #6B21A8; }
    .ed-w-chip--default { background: #F1F5F9; color: #475569; }

    .ed-w-empty {
        padding: 3rem 1rem;
        text-align: center;
    }

    .ed-w-empty svg {
        width: 40px;
        height: 40px;
        color: #CBD5E1;
        margin: 0 auto 0.75rem;
    }

    .ed-w-empty span {
        font-size: 0.88rem;
        font-weight: 600;
        color: #94A3B8;
    }

    @media (max-width: 900px) {
        .ed-w-hero { grid-template-columns: 1fr; }
        .ed-w-section { padding: 1.25rem; }
    }
</style>

<div class="ed-wallet">

    {{-- ═══ ROADMAP NOTICE BANNER ═══ --}}
    <div class="ed-w-banner">
        <div class="ed-w-banner-icon">💳</div>
        <div>
            <strong>Прямая оплата карточкой при бронировании</strong>
            <p>
                Оплата уроков и пакетов происходит прямо при оформлении записи с помощью банковской карты (Visa / Mastercard / Белкарт / ЕРИП). 
                Внутренний баланс выведен из обращения и будет доступен позже в соответствии с дорожной картой платформы. Ниже представлен полный финансовый учет оплат и выданных чеков.
            </p>
        </div>
    </div>

    {{-- ═══ HERO FINANCIAL STATS ═══ --}}
    <div class="ed-w-hero">
        <div class="ed-w-hero-card ed-w-hero-card--spent">
            <div class="ed-w-hero-glow"></div>
            <div>
                <div class="ed-w-hero-label">
                    <span style="width:7px;height:7px;border-radius:50%;background:var(--ed-lime);"></span>
                    Всего оплачено за уроки
                </div>
                <div class="ed-w-hero-amount">{!! $totalSpentHtml !!}</div>
            </div>
            <div class="ed-w-hero-hint">Подтверждённые оплаты пакетов и отдельных занятий</div>
        </div>

        <div class="ed-w-hero-card ed-w-hero-card--refunded">
            <div class="ed-w-hero-glow"></div>
            <div>
                <div class="ed-w-hero-label">
                    <span style="width:7px;height:7px;border-radius:50%;background:#38BDF8;"></span>
                    Возвраты на карту
                </div>
                <div class="ed-w-hero-amount">{!! $totalRefundedHtml !!}</div>
            </div>
            <div class="ed-w-hero-hint">Сумма отмененных бронирований, возвращенных на карту</div>
        </div>

        <div class="ed-w-hero-card ed-w-hero-card--safe">
            <div class="ed-w-hero-glow"></div>
            <div>
                <div class="ed-w-hero-label">
                    <span style="width:7px;height:7px;border-radius:50%;background:#C084FC;"></span>
                    Защита сделки
                </div>
                <div class="ed-w-hero-amount">100%</div>
            </div>
            <div class="ed-w-hero-hint">Деньги удерживаются платформой до проведения каждого занятия</div>
        </div>
    </div>

    {{-- ═══ CARD TRANSACTIONS HISTORY (УЧЁТ ДЕНЕГ) ═══ --}}
    <div class="ed-w-section">
        <div class="ed-w-section-header">
            <div>
                <h3 class="ed-w-section-title">История платежей с карты</h3>
                <div class="ed-w-section-subtitle">Официальные чеки и результаты банковских транзакций</div>
            </div>
            <span class="ed-w-pay-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Банковский эквайринг
            </span>
        </div>

        <table class="ed-w-table">
            <thead>
                <tr>
                    <th>Статус</th>
                    <th>Способ оплаты</th>
                    <th>Сумма</th>
                    <th>ID транзакции</th>
                    <th>Дата и время</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    @php
                        $status = (string) $tx->status;
                        $amountHtml = \App\Support\BynMoneyFormatter::format((string) $tx->amount)->toHtml();

                        $chipMod = match ($status) {
                            'success', 'completed' => 'card',
                            'pending'              => 'pending',
                            'refunded'             => 'refund',
                            'failed'               => 'failed',
                            default                => 'default',
                        };

                        $statusLabel = match ($status) {
                            'success', 'completed' => 'Оплачено',
                            'pending'              => 'Обработка',
                            'refunded'             => 'Возвращено',
                            'failed'               => 'Отклонено',
                            default                => 'Операция',
                        };

                        $methodLabel = match ((string) $tx->payment_method) {
                            'erip'      => 'ЕРИП',
                            'apple_pay' => 'Apple Pay',
                            'google_pay'=> 'Google Pay',
                            default     => 'Банковская карта',
                        };
                    @endphp
                    <tr>
                        <td><span class="ed-w-chip ed-w-chip--{{ $chipMod }}">{{ $statusLabel }}</span></td>
                        <td><strong>{{ $methodLabel }}</strong></td>
                        <td class="ed-w-tx-amount">{!! $amountHtml !!}</td>
                        <td><code style="font-size:0.78rem;color:#64748B;">{{ $tx->gateway_transaction_id ?? ('TX-' . $tx->id) }}</code></td>
                        <td>{{ $tx->created_at->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="ed-w-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                <span>История платежей с карт пока пуста. Забронируйте ваш первый урок!</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ═══ LESSON LEDGER REGISTRY ═══ --}}
    <div class="ed-w-section">
        <div class="ed-w-section-header">
            <div>
                <h3 class="ed-w-section-title">Реестр оплат уроков</h3>
                <div class="ed-w-section-subtitle">Детализированный учет расходов по занятиям и репетиторам</div>
            </div>
            <span class="ed-w-pay-badge">Учёт Edusfera</span>
        </div>

        <table class="ed-w-table">
            <thead>
                <tr>
                    <th>Тип операции</th>
                    <th>Сумма</th>
                    <th>Преподаватель / Назначение</th>
                    <th>Дата и время</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    @php
                        $type = (string) $entry->type;
                        $amountHtml = \App\Support\BynMoneyFormatter::format((string) $entry->amount)->toHtml();

                        $chipMod = match ($type) {
                            'payment' => 'card',
                            'refund'  => 'refund',
                            'hold'    => 'pending',
                            default   => 'default',
                        };

                        $typeLabel = match ($type) {
                            'payment' => 'Оплата урока',
                            'refund'  => 'Возврат средств',
                            'hold'    => 'Гарантийный резерв',
                            default   => 'Финоперация',
                        };

                        $tutorName = $entry->lesson?->tutor?->name ?? 'Преподаватель Edusfera';
                        $details = match ($type) {
                            'payment' => 'Оплачен урок с ' . $tutorName,
                            'refund'  => 'Возврат за отменённое занятие (' . $tutorName . ')',
                            'hold'    => 'Зарезервировано на время проведения (' . $tutorName . ')',
                            default   => 'Оплата с карты при бронировании',
                        };
                    @endphp
                    <tr>
                        <td><span class="ed-w-chip ed-w-chip--{{ $chipMod }}">{{ $typeLabel }}</span></td>
                        <td class="ed-w-tx-amount">{!! $amountHtml !!}</td>
                        <td>{{ $details }}</td>
                        <td>{{ $entry->created_at->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="ed-w-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Записи в реестре оплат пока отсутствуют.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-filament-panels::page>
