<x-filament-panels::page>
    @php
        $userRole = auth()->user()?->role;
    @endphp

    <style>
        .ef-chat {
            --ef-border: #e5e7eb;
            --ef-text: #111111;
            --ef-muted: #6b7280;
            --ef-soft: #f5f7fb;
            --ef-soft-2: #f3f4f6;
            --ef-lime: #c6ff33;
            --ef-lime-deep: #a3d51f;
            --ef-violet: #7d39eb;
            --ef-black: #121212;
            --ef-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            display: grid;
            grid-template-columns: 360px minmax(0, 1fr);
            gap: 18px;
            min-height: calc(100vh - 11rem);
        }

        .ef-panel {
            background: #fff;
            border: 1px solid var(--ef-border);
            border-radius: 24px;
            box-shadow: var(--ef-shadow);
            overflow: hidden;
        }

        .ef-sidebar {
            display: flex;
            flex-direction: column;
            min-height: 72vh;
        }

        .ef-sidebar-top {
            padding: 18px;
            border-bottom: 1px solid var(--ef-border);
            background: linear-gradient(180deg, #ffffff 0%, #f7ffdf 100%);
        }

        .ef-sidebar-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 900;
            color: var(--ef-text);
        }

        .ef-sidebar-copy {
            margin: 0.35rem 0 0;
            color: var(--ef-muted);
            font-size: 0.875rem;
            line-height: 1.45;
        }

        .ef-search {
            margin-top: 1rem;
        }

        .ef-search input,
        .ef-compose-field textarea {
            width: 100%;
            border-radius: 16px;
            border: 1px solid #d7dbe4;
            background: #fff;
            padding: 0.9rem 1rem;
            font-size: 0.95rem;
            color: var(--ef-text);
            outline: none;
        }

        .ef-search input:focus,
        .ef-compose-field textarea:focus {
            border-color: var(--ef-violet);
            box-shadow: 0 0 0 4px rgba(125, 57, 235, 0.12);
        }

        .ef-filters {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.9rem;
            overflow-x: auto;
            padding-bottom: 0.2rem;
        }

        .ef-filter {
            flex: 0 0 auto;
            min-height: 2.4rem;
            padding: 0 0.95rem;
            border-radius: 999px;
            border: 1px solid #d7dbe4;
            background: #fff;
            color: #1f2937;
            font-size: 0.82rem;
            font-weight: 800;
            cursor: pointer;
        }

        .ef-filter.is-active {
            background: var(--ef-black);
            border-color: var(--ef-black);
            color: #fff;
        }

        .ef-list {
            overflow: auto;
            padding: 0.35rem 0;
        }

        .ef-item {
            width: 100%;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-align: left;
            padding: 0.9rem 1rem;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 0.8rem;
            align-items: start;
            border-bottom: 1px solid #f1f3f6;
        }

        .ef-item:hover,
        .ef-item.is-active {
            background: #fafafc;
        }

        .ef-avatar {
            width: 3rem;
            height: 3rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #ece8ff, #f7ffdf);
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 900;
            position: relative;
            flex: 0 0 auto;
        }

        .ef-avatar::after {
            content: "";
            position: absolute;
            right: 0.05rem;
            bottom: 0.05rem;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 999px;
            background: #22c55e;
            border: 2px solid #fff;
        }

        .ef-item-name {
            margin: 0;
            font-size: 0.96rem;
            font-weight: 900;
            color: var(--ef-text);
        }

        .ef-item-subject {
            display: inline-flex;
            margin-top: 0.28rem;
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .ef-item-preview {
            display: block;
            margin-top: 0.45rem;
            color: var(--ef-muted);
            font-size: 0.84rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ef-item-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.45rem;
        }

        .ef-item-time {
            color: var(--ef-muted);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .ef-unread {
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 0.42rem;
            border-radius: 999px;
            background: var(--ef-lime);
            color: #111;
            font-size: 0.72rem;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .ef-room {
            display: flex;
            flex-direction: column;
            min-height: 72vh;
            background:
                radial-gradient(circle at top left, rgba(198, 255, 51, 0.18) 0, transparent 28%),
                radial-gradient(circle at top right, rgba(125, 57, 235, 0.11) 0, transparent 35%),
                #fff;
        }

        .ef-room-header {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid var(--ef-border);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .ef-back {
            display: none;
            width: 2.5rem;
            height: 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #d7dbe4;
            background: #fff;
            color: #111;
            text-decoration: none;
        }

        .ef-room-heading {
            min-width: 0;
            flex: 1 1 auto;
        }

        .ef-room-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 900;
            color: var(--ef-text);
        }

        .ef-room-copy {
            margin: 0.22rem 0 0;
            color: var(--ef-muted);
            font-size: 0.8rem;
        }

        .ef-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.55rem;
        }

        .ef-btn {
            min-height: 2.7rem;
            padding: 0 1rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.88rem;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
        }

        .ef-btn-primary {
            background: var(--ef-lime);
            border-color: var(--ef-lime);
            color: #111;
        }

        .ef-btn-primary:hover {
            background: var(--ef-lime-deep);
            border-color: var(--ef-lime-deep);
        }

        .ef-btn-ghost {
            background: #fff;
            border-color: #d7dbe4;
            color: #111827;
        }

        .ef-messages {
            flex: 1 1 auto;
            overflow: auto;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            padding: 1rem 1rem 1.2rem;
        }

        .ef-row {
            display: flex;
        }

        .ef-row.mine {
            justify-content: flex-end;
        }

        .ef-row.their {
            justify-content: flex-start;
        }

        .ef-row.system {
            justify-content: center;
        }

        .ef-bubble {
            max-width: min(82%, 42rem);
            border-radius: 1.1rem;
            padding: 0.85rem 0.95rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .ef-bubble.mine {
            background: var(--ef-black);
            color: #fff;
            border-bottom-right-radius: 0.4rem;
        }

        .ef-bubble.their {
            background: var(--ef-soft-2);
            color: #111827;
            border-bottom-left-radius: 0.4rem;
        }

        .ef-bubble.system {
            max-width: min(90%, 46rem);
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            color: #4b5563;
            box-shadow: none;
        }

        .ef-bubble.system.is-success {
            background: #ecfccb;
            border-color: #bef264;
            color: #365314;
        }

        .ef-bubble-text {
            margin: 0;
            font-size: 0.92rem;
            line-height: 1.55;
            word-break: break-word;
        }

        .ef-bubble-foot {
            margin: 0.45rem 0 0;
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.72rem;
            opacity: 0.82;
        }

        .ef-edit-link {
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font-size: 0.72rem;
            font-weight: 800;
            text-decoration: underline;
            cursor: pointer;
        }

        .ef-masked {
            color: #6b7280;
            font-style: italic;
            font-weight: 600;
        }

        .ef-compose {
            position: sticky;
            bottom: 0;
            z-index: 4;
            border-top: 1px solid var(--ef-border);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            padding: 0.9rem 1rem calc(0.9rem + env(safe-area-inset-bottom));
        }

        .ef-review-card {
            margin: 0.5rem 1rem 0;
            padding: 1rem;
            border-radius: 20px;
            border: 1px solid #d7dbe4;
            background: linear-gradient(180deg, #ffffff 0%, #fbffef 100%);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
        }

        .ef-review-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 900;
            color: var(--ef-text);
        }

        .ef-review-copy {
            margin: 0.45rem 0 0;
            color: var(--ef-muted);
            font-size: 0.88rem;
            line-height: 1.55;
        }

        .ef-stars {
            display: flex;
            gap: 0.45rem;
            margin-top: 0.9rem;
        }

        .ef-star {
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 999px;
            border: 1px solid #d7dbe4;
            background: #fff;
            color: #f59e0b;
            font-size: 1rem;
            font-weight: 900;
            cursor: pointer;
        }

        .ef-star.is-active {
            background: #fff7ed;
            border-color: #f59e0b;
        }

        .ef-review-upsell {
            margin-top: 0.9rem;
            padding: 0.95rem 1rem;
            border-radius: 16px;
            background: #111827;
            color: #fff;
        }

        .ef-review-upsell p {
            margin: 0;
            color: rgba(255,255,255,.78);
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .ef-alert {
            margin-bottom: 0.75rem;
            border-radius: 16px;
            border: 1px solid #d9f99d;
            background: #f7fee7;
            color: #3f6212;
            padding: 0.8rem 0.9rem;
            font-size: 0.85rem;
            line-height: 1.45;
        }

        .ef-quick-replies {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
            margin-bottom: 0.7rem;
        }

        .ef-quick-reply {
            flex: 0 0 auto;
            min-height: 2.35rem;
            padding: 0 0.9rem;
            border-radius: 999px;
            border: 1px solid #d7dbe4;
            background: #fff;
            color: #111827;
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
        }

        .ef-compose-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: end;
        }

        .ef-compose-field {
            position: relative;
        }

        .ef-compose-field textarea {
            min-height: 3.4rem;
            max-height: 8rem;
            resize: vertical;
            padding-right: 3.3rem;
        }

        .ef-attach {
            position: absolute;
            right: 0.8rem;
            bottom: 0.8rem;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 999px;
            background: #f3f4f6;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            cursor: not-allowed;
        }

        .ef-compose-meta {
            margin-top: 0.55rem;
            color: var(--ef-muted);
            font-size: 0.74rem;
            line-height: 1.4;
        }

        .ef-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 18rem;
            padding: 1.5rem;
        }

        .ef-empty-box {
            width: 100%;
            max-width: 28rem;
            padding: 1.5rem;
            border-radius: 20px;
            border: 1px dashed #d1d5db;
            background: #fafafa;
            text-align: center;
        }

        .ef-empty-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--ef-text);
        }

        .ef-empty-text {
            margin: 0.55rem 0 0;
            color: var(--ef-muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 1100px) {
            .ef-chat {
                grid-template-columns: 1fr;
            }

            .ef-sidebar {
                min-height: auto;
            }

            .ef-list {
                max-height: 24rem;
            }
        }

        @media (max-width: 768px) {
            .ef-chat {
                gap: 1rem;
                min-height: auto;
            }

            .ef-panel {
                border-radius: 20px;
            }

            .ef-room-header {
                position: sticky;
                top: 0;
                padding: 0.9rem;
            }

            .ef-back {
                display: inline-flex;
            }

            .ef-actions {
                width: 100%;
                justify-content: flex-start;
                margin-top: 0.6rem;
            }

            .ef-bubble {
                max-width: 88%;
            }

            .ef-compose-row {
                grid-template-columns: 1fr;
            }

            .ef-btn {
                width: 100%;
            }
        }
    </style>

    <div class="ef-chat" wire:poll.5s>
        <aside class="ef-panel ef-sidebar">
            <div class="ef-sidebar-top">
                <h2 class="ef-sidebar-title">Чаты</h2>
                <p class="ef-sidebar-copy">Все обращения по урокам и заявкам собраны в одном месте.</p>

                <div class="ef-search">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Поиск по имени или предмету"
                    >
                </div>

                <div class="ef-filters">
                    <button type="button" wire:click="setFilter('all')" class="ef-filter {{ $filter === 'all' ? 'is-active' : '' }}">Все</button>
                    <button type="button" wire:click="setFilter('unread')" class="ef-filter {{ $filter === 'unread' ? 'is-active' : '' }}">Непрочитанные</button>
                    <button type="button" wire:click="setFilter('paid')" class="ef-filter {{ $filter === 'paid' ? 'is-active' : '' }}">Оплаченные</button>
                </div>
            </div>

            <div class="ef-list">
                @if ($conversations->isNotEmpty())
                    @foreach ($conversations as $conversation)
                        @php
                            $otherUser = auth()->id() === $conversation->tutor_id ? $conversation->student : $conversation->tutor;
                            $lastMessage = $conversation->messages->first();
                            $subject = $conversation->lesson?->tutor?->tutorProfile?->subjects[0]
                                ?? $conversation->tutor?->tutorProfile?->subjects[0]
                                ?? 'Общий чат';
                            $isActive = $conversationId === $conversation->id;
                        @endphp

                        <button type="button" wire:click="openConversation({{ $conversation->id }})" class="ef-item {{ $isActive ? 'is-active' : '' }}">
                            <span class="ef-avatar">{{ mb_substr($otherUser?->name ?? 'U', 0, 1) }}</span>

                            <span style="min-width: 0;">
                                <p class="ef-item-name">{{ $otherUser?->name ?? 'Пользователь' }}</p>
                                <span class="ef-item-subject">{{ $subject }}</span>
                                <span class="ef-item-preview">{{ $lastMessage?->message ?? 'Начните диалог' }}</span>
                            </span>

                            <span class="ef-item-meta">
                                <span class="ef-item-time">{{ optional($conversation->last_message_at)->setTimezone(config('booking.display_timezone'))->format('H:i') }}</span>
                                @if ($conversation->unread_count > 0)
                                    <span class="ef-unread">{{ $conversation->unread_count }}</span>
                                @endif
                            </span>
                        </button>
                    @endforeach
                @else
                    <div class="ef-empty" style="min-height: 14rem;">
                        <div class="ef-empty-box">
                            <p class="ef-empty-title">Чатов пока нет</p>
                            <p class="ef-empty-text">
                                @if (in_array($userRole, ['student', 'parent'], true))
                                    Откройте анкету репетитора и начните диалог прямо из каталога.
                                @else
                                    Как только ученик напишет вам или оплатит урок, чат появится здесь.
                                @endif
                            </p>
                            @if (in_array($userRole, ['student', 'parent'], true))
                                <a href="/tutors" class="ef-btn ef-btn-primary">Перейти в каталог</a>
                            @else
                                <a href="/admin/lesson-requests" class="ef-btn ef-btn-ghost">Открыть заявки</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </aside>

        <section class="ef-panel ef-room">
            @if ($selectedConversation)
                @php
                    $otherUser = auth()->id() === $selectedConversation->tutor_id ? $selectedConversation->student : $selectedConversation->tutor;
                    $subject = $selectedConversation->lesson?->tutor?->tutorProfile?->subjects[0]
                        ?? $selectedConversation->tutor?->tutorProfile?->subjects[0]
                        ?? 'Диалог';
                @endphp

                <header class="ef-room-header">
                    <button type="button" class="ef-back" onclick="window.history.back()">←</button>
                    <span class="ef-avatar">{{ mb_substr($otherUser?->name ?? 'U', 0, 1) }}</span>

                    <div class="ef-room-heading">
                        <h3 class="ef-room-title">{{ $otherUser?->name ?? 'Пользователь' }}</h3>
                        <p class="ef-room-copy">{{ $subject }} · {{ $selectedConversation->lesson_id ? 'Активный диалог по уроку' : 'Личный чат' }}</p>
                    </div>

                    <div class="ef-actions">
                        @if ($headerAction)
                            <a
                                href="{{ $headerAction['url'] }}"
                                @if ($headerAction['external']) target="_blank" rel="noopener noreferrer" @endif
                                class="ef-btn {{ $headerAction['variant'] === 'primary' ? 'ef-btn-primary' : 'ef-btn-ghost' }}"
                            >
                                {{ $headerAction['label'] }}
                            </a>
                        @endif

                        @if ($contactsUnlocked && count($contactActions) > 0)
                            @foreach ($contactActions as $action)
                                <a
                                    href="{{ $action['url'] }}"
                                    @if ($action['external']) target="_blank" rel="noopener noreferrer" @endif
                                    class="ef-btn ef-btn-ghost"
                                >
                                    {{ $action['label'] }}
                                </a>
                            @endforeach
                        @endif
                    </div>
                </header>

                <div class="ef-messages">
                    @foreach ($messages as $message)
                        @php
                            $isMine = $message->sender_id === auth()->id();
                            $isSystem = (bool) $message->is_system;
                            $isUnlockSuccess = ($message->meta['variant'] ?? null) === 'contacts_unlocked';
                        @endphp

                        <div class="ef-row {{ $isSystem ? 'system' : ($isMine ? 'mine' : 'their') }}">
                            <div class="ef-bubble {{ $isSystem ? 'system' : ($isMine ? 'mine' : 'their') }} {{ $isUnlockSuccess ? 'is-success' : '' }}">
                                <p class="ef-bubble-text">{!! $this->formatMessage($message) !!}</p>
                                <p class="ef-bubble-foot">
                                    <span>{{ $message->created_at->setTimezone(config('booking.display_timezone'))->format('d.m H:i') }}</span>
                                    @if ($message->is_read && $isMine && ! $isSystem)
                                        <span>✓✓ прочитано</span>
                                    @elseif ($isMine && ! $isSystem)
                                        <span>✓ отправлено</span>
                                    @endif
                                    @if ($isMine && ! $isSystem && $message->created_at->gt(now()->subMinutes(5)))
                                        <button type="button" wire:click="beginEditMessage({{ $message->id }})" class="ef-edit-link">Изменить</button>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($reviewState)
                    <div class="ef-review-card">
                        @if ($reviewState['can_prompt'])
                            <p class="ef-review-title">Как прошло занятие с {{ $otherUser?->name ?? 'репетитором' }}?</p>
                            <p class="ef-review-copy">Оценка 4-5 попадёт в публичный профиль. Оценка 1-3 уйдёт в закрытый разбор качества внутри Edusfera.</p>

                            <div class="ef-stars">
                                @for ($star = 1; $star <= 5; $star++)
                                    <button type="button" wire:click="pickReviewRating({{ $star }})" class="ef-star {{ $reviewRating !== null && $reviewRating >= $star ? 'is-active' : '' }}">★</button>
                                @endfor
                            </div>

                            <div style="margin-top: 0.8rem;">
                                <textarea wire:model="reviewText" class="ef-input" rows="3" placeholder="Что понравилось больше всего?"></textarea>
                            </div>

                            <div style="display:flex;gap:.65rem;flex-wrap:wrap;margin-top:.8rem;">
                                <button type="button" wire:click="submitReview" class="ef-btn ef-btn-primary" style="margin-top:0;width:auto;">Отправить отзыв</button>
                            </div>
                        @else
                            <p class="ef-review-title">Отзыв уже сохранён</p>
                            <p class="ef-review-copy">
                                @if ($reviewSuccessMessage)
                                    {{ $reviewSuccessMessage }}
                                @else
                                    Спасибо за оценку {{ $reviewState['rating'] }}/5. Она уже учтена в системе качества Edusfera.
                                @endif
                            </p>
                        @endif

                        @if ($reviewState['show_upsell'])
                            <div class="ef-review-upsell">
                                <strong style="display:block;font-size:1rem;font-weight:900;">Рады, что вам понравилось</strong>
                                <p style="margin-top:.35rem;">Зафиксируйте удобное расписание и сэкономьте на длинной траектории. Пакет из 8 занятий даст скидку 10%.</p>
                                <a href="{{ $reviewState['upsell_url'] }}" class="ef-btn ef-btn-primary" style="margin-top:.8rem;width:auto;">Оформить пакет</a>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="ef-compose">
                    @if ($warningMessage)
                        <div class="ef-alert">{{ $warningMessage }}</div>
                    @endif

                    @if ($editingMessageId)
                        <div class="ef-alert" style="background:#f5f3ff;border-color:#d8b4fe;color:#5b21b6;">
                            Редактирование сообщения активно. Сохраните изменения или отмените редактирование.
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.7rem;">
                                <button type="button" wire:click="saveEditedMessage" class="ef-btn ef-btn-primary" style="width:auto;">Сохранить</button>
                                <button type="button" wire:click="cancelEdit" class="ef-btn ef-btn-ghost" style="width:auto;">Отмена</button>
                            </div>
                        </div>
                    @endif

                    @if (count($quickReplies) > 0)
                        <div class="ef-quick-replies">
                            @foreach ($quickReplies as $reply)
                                <button type="button" wire:click="useQuickReply(@js($reply))" class="ef-quick-reply">{{ $reply }}</button>
                            @endforeach
                        </div>
                    @endif

                    <form wire:submit="sendMessage">
                        <div class="ef-compose-row">
                            <label class="ef-compose-field">
                                <textarea
                                    wire:model="messageText"
                                    rows="2"
                                    maxlength="500"
                                    placeholder="Напишите сообщение"
                                ></textarea>
                                <span class="ef-attach" title="Файлы появятся на следующем этапе MVP">📎</span>
                            </label>

                            <button type="submit" class="ef-btn ef-btn-primary">Отправить</button>
                        </div>

                        <div class="ef-compose-meta">
                            Голосовые сообщения отключены на этапе MVP. Контакты откроются автоматически после оплаты первого занятия.
                        </div>
                    </form>
                </div>
            @else
                <div class="ef-empty">
                    <div class="ef-empty-box">
                        <p class="ef-empty-title">Выберите диалог</p>
                        <p class="ef-empty-text">
                            Здесь будет вся коммуникация по урокам, оплате и подтверждению времени без переходов в сторонние мессенджеры.
                        </p>
                        @if (in_array($userRole, ['student', 'parent'], true))
                            <a href="/tutors" class="ef-btn ef-btn-primary">Найти репетитора</a>
                        @else
                            <a href="/admin/lesson-requests" class="ef-btn ef-btn-ghost">Перейти к заявкам</a>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
