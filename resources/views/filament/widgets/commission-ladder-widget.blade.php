<div class="tutor-card">
    <div class="tutor-comm-top">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px;">
                <span class="tutor-card-label">SaaS Подписка</span>
                <span class="tutor-chip tutor-chip--done" style="padding: 3px 8px; font-size: 11px;">
                    100% дохода вам
                </span>
                @if ($isFounder)
                    <span class="tutor-chip" style="padding: 3px 8px; font-size: 11px; background: rgba(245, 158, 11, 0.12); color: #d97706; border-color: rgba(245, 158, 11, 0.3);">
                        ★ Статус Основателя (Founder)
                    </span>
                @endif
            </div>

            <div class="tutor-comm-rate" style="gap: 8px;">
                <span class="tutor-comm-rate-value">{{ $planTitle }}</span>
                <span class="tutor-comm-rate-unit" style="font-size: 0.95rem; font-weight: 700; color: #6B7280;">
                    ({{ $monthlyPrice }} BYN/мес)
                </span>
                <span class="tutor-chip {{ $statusBadge['color'] === 'active' || $statusBadge['color'] === 'trial' ? 'tutor-chip--active' : '' }}" style="font-size: 11px; padding: 4px 8px;">
                    {{ $statusBadge['label'] }} · {{ $statusBadge['hint'] }}
                </span>
            </div>
        </div>

        <div class="tutor-comm-revenue">
            <span class="tutor-card-label">Прямой доход на карту · {{ now()->translatedFormat('F') }}</span>
            <p class="tutor-comm-revenue-value">{{ $monthlyRevenue }} <span class="tutor-comm-revenue-unit">BYN</span></p>
        </div>
    </div>

    <div class="tutor-comm-progress">
        <div class="tutor-comm-progress-head">
            <span>
                @if ($maxResponses === null)
                    <strong style="color: #059669;">Безлимитные отклики</strong> на заявки учеников (Premium)
                @elseif ($maxResponses > 0)
                    Квота откликов: <strong>{{ $responsesUsed }} / {{ $maxResponses }}</strong> в этом месяце
                    (осталось <strong>{{ max(0, $maxResponses - $responsesUsed) }}</strong>)
                @else
                    <strong>0 откликов</strong> в тарифе Basic (только входящие заявки из каталога)
                @endif
            </span>
            <span>
                @if ($maxResponses !== null && $maxResponses > 0)
                    {{ $progressPercent }}% лимита
                @else
                    {{ $isTrial ? 'Пробный доступ' : 'Активный тариф' }}
                @endif
            </span>
        </div>

        <div class="tutor-progress" style="margin-top: 8px;">
            <div style="width: {{ $progressPercent }}%;"></div>
        </div>

        <div class="tutor-chips" style="margin-top: 16px;">
            @foreach ($plansInfo as $item)
                <span class="tutor-chip {{ $item['isActive'] ? 'tutor-chip--active' : '' }}">
                    @if ($item['isActive'])
                        <span class="tutor-chip-dot"></span>
                    @endif
                    <strong>{{ $item['name'] }}</strong> ({{ $item['price'] }}) · {{ $item['responses'] }}
                </span>
            @endforeach
        </div>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 20px;">
        <a href="/admin/tutor-subscription-page" class="tutor-link" style="margin-top: 0;">
            Управление тарифом и счетами ЕРИП
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </a>

        <a href="/admin/lessons" class="tutor-link" style="margin-top: 0; color: #6B7280;">
            Расписание и уроки
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </a>
    </div>
</div>
