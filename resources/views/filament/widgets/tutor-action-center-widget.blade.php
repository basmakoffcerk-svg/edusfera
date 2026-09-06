@php
    $tz = config('booking.display_timezone', 'Europe/Minsk');
@endphp

<div class="tutor-actions">

    {{-- ── Заявка, ждущая ответа ─────────────────────────────────── --}}
    <div class="tutor-card" style="display:flex; flex-direction:column;">
        <div class="tutor-card-head">
            <span class="tutor-card-label">Заявки учеников</span>
            @if ($newRequestsCount > 1)
                <a href="/admin/lesson-requests" class="tutor-link" style="margin-top:0">
                    все {{ $newRequestsCount }} →
                </a>
            @endif
        </div>

        @if ($latestRequest)
            <div class="tutor-request">
                <div class="tutor-request-row">
                    <p class="tutor-request-name">
                        {{ $latestRequest->student?->name ?? $latestRequest->parent?->name ?? 'Ученик' }}
                    </p>
                    <p class="tutor-request-time">
                        {{ $latestRequest->start_time->timezone($tz)->translatedFormat('j M, H:i') }}
                    </p>
                </div>
                <p class="tutor-request-note">
                    {{ $latestRequest->package_label }} · ждёт вашего подтверждения
                </p>

                <div class="tutor-cta-row">
                    <a href="/admin/lesson-requests" class="tutor-cta tutor-cta--primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Ответить на заявку
                    </a>
                    <a href="{{ $chatUrl }}" class="tutor-cta tutor-cta--ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
                        Чат с учеником
                    </a>
                </div>
            </div>
        @else
            <div class="tutor-empty">
                <p class="tutor-empty-title">Всё чисто ✨</p>
                <p class="tutor-empty-text">
                    Новых заявок нет. Заполненные слоты в календаре помогают ученикам находить вас.
                </p>
            </div>
        @endif
    </div>

    {{-- ── Быстрые действия (навигация на смартфоне) ─────────────── --}}
    <div class="tutor-card">
        <span class="tutor-card-label">Быстрые действия</span>
        <div class="tutor-tiles">
            <a href="/admin/lessons" class="tutor-tile">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                <span>Расписание</span>
            </a>
            <a href="/admin/lesson-requests" class="tutor-tile">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.75a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75H3.75a.75.75 0 0 1-.75-.75V13.5Zm0-3.75V7.5A2.25 2.25 0 0 1 4.5 5.25h15a2.25 2.25 0 0 1 2.25 2.25v11.25A2.25 2.25 0 0 1 19.5 21h-15a2.25 2.25 0 0 1-2.25-2.25V9.75Z"/></svg>
                <span>Заявки</span>
                @if ($newRequestsCount > 0)
                    <span class="tutor-tile-badge">{{ $newRequestsCount }}</span>
                @endif
            </a>
            <a href="/admin/messages" class="tutor-tile">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
                <span>Сообщения</span>
                @if ($unreadMessages > 0)
                    <span class="tutor-tile-badge">{{ $unreadMessages }}</span>
                @endif
            </a>
            <a href="/admin/tutor-availability" class="tutor-tile">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>Мои слоты</span>
            </a>
        </div>
    </div>
</div>
