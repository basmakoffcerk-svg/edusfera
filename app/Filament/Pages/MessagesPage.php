<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\Message;
use App\Models\User;
use App\Notifications\LessonLowRatingNotification;
use App\Services\ChatUnreadCounter;
use App\Services\ChatService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class MessagesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $slug = 'messages';

    protected static string $view = 'filament.pages.messages-page';

    protected static ?string $navigationLabel = 'Сообщения';

    protected static ?string $title = 'Сообщения';

    protected ?string $subheading = 'Общение с учениками и репетиторами внутри платформы.';

    protected ?string $maxContentWidth = 'full';

    public ?int $conversationId = null;

    public string $messageText = '';

    public string $search = '';

    public string $filter = 'all';

    public ?int $editingMessageId = null;

    public string $editingMessageText = '';

    public ?string $warningMessage = null;

    public ?int $reviewRating = null;

    public string $reviewText = '';

    public ?string $reviewSuccessMessage = null;

    protected $queryString = [
        'conversationId' => ['as' => 'conversation'],
        'filter' => ['except' => 'all'],
    ];

    public function mount(?int $conversation = null): void
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'tutor', 'student', 'parent'], true), 403);

        $requestedConversationId = $conversation ?? $this->conversationId;

        if ($requestedConversationId && ! $this->authorizedConversationsQuery()->whereKey($requestedConversationId)->exists()) {
            $requestedConversationId = null;
        }

        $this->conversationId = $requestedConversationId ?? $this->conversations()->first()?->id;

        if ($this->conversationId) {
            app(ChatService::class)->markAsRead(auth()->id(), $this->conversationId);
        }
    }

    public function openConversation(int $conversationId): void
    {
        if (! $this->authorizedConversationsQuery()->whereKey($conversationId)->exists()) {
            return;
        }

        $this->conversationId = $conversationId;
        $this->warningMessage = null;
        $this->editingMessageId = null;
        $this->editingMessageText = '';

        app(ChatService::class)->markAsRead(auth()->id(), $conversationId);
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'unread', 'paid'], true)) {
            return;
        }

        $this->filter = $filter;
    }

    public function useQuickReply(string $reply): void
    {
        $this->messageText = $reply;
    }

    public function sendMessage(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $result = app(ChatService::class)->sendMessage(
            conversationId: $this->conversationId,
            senderId: auth()->id(),
            message: $this->messageText,
        );

        $this->messageText = '';
        $this->warningMessage = $result['warning'];
        app(ChatService::class)->markAsRead(auth()->id(), $this->conversationId);
    }

    public function pickReviewRating(int $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            return;
        }

        $this->reviewRating = $rating;
    }

    public function submitReview(): void
    {
        $conversation = $this->selectedConversation();

        if (! $conversation?->lesson) {
            return;
        }

        $lesson = $conversation->lesson->fresh();
        $user = auth()->user();

        if (! $user || ! in_array($user->id, array_filter([$lesson->student_id, $lesson->parent_id]), true)) {
            return;
        }

        if (
            $lesson->status !== Lesson::STATUS_COMPLETED
            || $lesson->payment_status !== Lesson::PAYMENT_PAID
            || $lesson->feedback_submitted_at !== null
            || $lesson->end_time->addHour()->isFuture()
        ) {
            return;
        }

        if ($this->reviewRating === null || $this->reviewRating < 1 || $this->reviewRating > 5) {
            Notification::make()
                ->title('Выберите оценку от 1 до 5')
                ->warning()
                ->send();

            return;
        }

        $lesson->update([
            'student_rating' => $this->reviewRating,
            'student_feedback' => trim($this->reviewText) !== '' ? trim($this->reviewText) : null,
            'is_public_review' => $this->reviewRating >= 4,
            'feedback_submitted_at' => now('UTC'),
        ]);

        if ($lesson->is_public_review && $lesson->tutor?->tutorProfile) {
            $average = Lesson::query()
                ->where('tutor_id', $lesson->tutor_id)
                ->where('is_public_review', true)
                ->whereNotNull('student_rating')
                ->avg('student_rating');

            $lesson->tutor->tutorProfile->update([
                'rating_avg' => number_format((float) ($average ?? 0), 2, '.', ''),
            ]);
        }

        if ($this->reviewRating <= 3) {
            User::query()
                ->where('role', 'admin')
                ->each(fn (User $admin): mixed => $admin->notify(new LessonLowRatingNotification($lesson, $user, $this->reviewRating)));
        }

        $this->reviewSuccessMessage = $this->reviewRating >= 4
            ? 'Спасибо за отзыв. Если хотите закрепить удобное расписание, ниже уже доступен пакет занятий со скидкой.'
            : 'Спасибо за честную оценку. Команда Edusfera получит сигнал и проверит качество занятия.';

        $this->reviewText = '';
    }

    public function beginEditMessage(int $messageId): void
    {
        $message = $this->selectedConversationMessages()->firstWhere('id', $messageId);

        if (! $message || $message->sender_id !== auth()->id() || $message->created_at->lt(now()->subMinutes(5))) {
            return;
        }

        $this->editingMessageId = $message->id;
        $this->editingMessageText = $message->message;
    }

    public function saveEditedMessage(): void
    {
        if (! $this->editingMessageId) {
            return;
        }

        $message = Message::query()->findOrFail($this->editingMessageId);

        if ($message->sender_id !== auth()->id() || $message->created_at->lt(now()->subMinutes(5))) {
            return;
        }

        $text = trim($this->editingMessageText);

        if ($text === '' || mb_strlen($text) > 500) {
            Notification::make()
                ->title('Сообщение должно быть от 1 до 500 символов')
                ->danger()
                ->send();

            return;
        }

        $message->update(['message' => $text]);
        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->editingMessageId = null;
        $this->editingMessageText = '';
    }

    public function getViewData(): array
    {
        $selectedConversation = $this->selectedConversation();

        return [
            'conversations' => $this->conversations(),
            'selectedConversation' => $selectedConversation,
            'messages' => $this->selectedConversationMessages(),
            'contactsUnlocked' => $selectedConversation ? app(ChatService::class)->hasUnlockedContactsForConversation($selectedConversation) : false,
            'contactActions' => $selectedConversation ? $this->contactActions($selectedConversation) : [],
            'quickReplies' => $this->quickReplies(),
            'headerAction' => $selectedConversation ? $this->headerAction($selectedConversation) : null,
            'reviewState' => $selectedConversation ? $this->reviewState($selectedConversation) : null,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId === 'site-admin') {
            return $user?->role === 'admin';
        }

        return in_array($user?->role, ['tutor', 'student', 'parent'], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return Filament::getCurrentPanel()?->getId() === 'site-admin'
            ? 'Коммуникация'
            : 'Основное';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = app(ChatUnreadCounter::class)->countForUser(auth()->user());

        return $count > 0 ? (string) $count : null;
    }

    public function getHeading(): string
    {
        return 'Сообщения';
    }

    public function getSubheading(): ?string
    {
        return 'Все диалоги по урокам и обращениям из каталога собраны здесь.';
    }

    private function conversations(): Collection
    {
        $conversations = $this->authorizedConversationsQuery()
            ->with(['lesson', 'tutor.tutorProfile', 'student', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->withCount([
                'messages as unread_count' => function (Builder $query): Builder {
                    $user = auth()->user();

                    return $query
                        ->where(function (Builder $inner) use ($user): Builder {
                            return $inner->whereNull('sender_id')->orWhere('sender_id', '!=', $user->id);
                        })
                        ->where('is_read', false);
                },
            ])
            ->orderByDesc('last_message_at')
            ->get();

        $search = mb_strtolower(trim($this->search));

        if ($search !== '') {
            $conversations = $conversations->filter(function (Conversation $conversation) use ($search): bool {
                $otherUser = $this->otherUser($conversation);
                $subject = $this->conversationSubject($conversation);

                return str_contains(mb_strtolower((string) $otherUser?->name), $search)
                    || str_contains(mb_strtolower($subject), $search);
            })->values();
        }

        $conversations = match ($this->filter) {
            'unread' => $conversations->filter(fn (Conversation $conversation): bool => (int) $conversation->unread_count > 0)->values(),
            'paid' => $conversations->filter(fn (Conversation $conversation): bool => $conversation->lesson?->payment_status === \App\Models\Lesson::PAYMENT_PAID)->values(),
            default => $conversations,
        };

        return $conversations
            ->sortByDesc(fn (Conversation $conversation): int => (int) $conversation->unread_count > 0 ? 1 : 0)
            ->sortByDesc(fn (Conversation $conversation): int => $this->hasActiveRequest($conversation) ? 1 : 0)
            ->sortByDesc(fn (Conversation $conversation): int => optional($conversation->last_message_at)?->timestamp ?? 0)
            ->values();
    }

    private function selectedConversation(): ?Conversation
    {
        if (! $this->conversationId) {
            return null;
        }

        return $this->authorizedConversationsQuery()
            ->with(['lesson', 'tutor.tutorProfile', 'student'])
            ->find($this->conversationId);
    }

    private function selectedConversationMessages(): Collection
    {
        if (! $this->conversationId) {
            return collect();
        }

        if (! $this->authorizedConversationsQuery()->whereKey($this->conversationId)->exists()) {
            return collect();
        }

        return Message::query()
            ->with('sender')
            ->where('conversation_id', $this->conversationId)
            ->latest('created_at')
            ->limit(60)
            ->get()
            ->sortBy('created_at')
            ->values();
    }

    private function authorizedConversationsQuery(): Builder
    {
        $user = auth()->user();

        $query = Conversation::query();

        if ($user?->role === 'admin') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): Builder {
            return $builder->where('tutor_id', $user->id)->orWhere('student_id', $user->id);
        });
    }

    public function formatMessage(Message $message): Htmlable
    {
        $content = app(ChatService::class)->sanitizeMessageForDisplay($message->message);
        $content = e($content);
        $content = preg_replace('/\[(контакты скрыты)\]/u', '<span class="ef-masked">[$1]</span>', $content) ?? $content;
        $content = preg_replace('/\[(платежные данные скрыты)\]/u', '<span class="ef-masked">[$1]</span>', $content) ?? $content;

        return new HtmlString(nl2br($content));
    }

    private function contactActions(Conversation $conversation): array
    {
        $currentUser = auth()->user();
        $otherUser = $currentUser?->id === $conversation->tutor_id ? $conversation->student : $conversation->tutor;

        if (! $otherUser) {
            return [];
        }

        $actions = [];

        if ($otherUser->phone) {
            $actions[] = [
                'label' => 'Позвонить',
                'url' => 'tel:' . preg_replace('/\s+/', '', (string) $otherUser->phone),
                'external' => false,
            ];
        }

        $telegram = $otherUser->tutorProfile?->telegram_username;

        if ($telegram) {
            $actions[] = [
                'label' => 'Написать в Telegram',
                'url' => 'https://t.me/' . ltrim($telegram, '@'),
                'external' => true,
            ];
        }

        return $actions;
    }

    private function quickReplies(): array
    {
        if (auth()->user()?->role !== 'tutor') {
            return [];
        }

        return [
            'Да, это время свободно.',
            'Какой у вас текущий уровень подготовки?',
            'Оплатите через платформу, и мы сразу закрепим слот.',
        ];
    }

    private function headerAction(Conversation $conversation): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if (in_array($user->role, ['student', 'parent'], true)) {
            if ($conversation->lesson && $conversation->lesson->payment_status !== \App\Models\Lesson::PAYMENT_PAID) {
                return [
                    'label' => 'Забронировать',
                    'url' => route('checkout.show', $conversation->lesson),
                    'external' => false,
                    'variant' => 'primary',
                ];
            }

            $profile = $conversation->tutor?->tutorProfile;

            if ($profile) {
                return [
                    'label' => 'Забронировать',
                    'url' => route('tutors.show', $profile),
                    'external' => false,
                    'variant' => 'primary',
                ];
            }
        }

        if ($user->role === 'tutor') {
            return [
                'label' => 'Предложить время',
                'url' => '/admin/availability',
                'external' => false,
                'variant' => 'ghost',
            ];
        }

        return null;
    }

    private function otherUser(Conversation $conversation): mixed
    {
        return auth()->id() === $conversation->tutor_id
            ? $conversation->student
            : $conversation->tutor;
    }

    private function conversationSubject(Conversation $conversation): string
    {
        if ($conversation->lesson?->tutor?->tutorProfile?->subjects) {
            return (string) ($conversation->lesson->tutor->tutorProfile->subjects[0] ?? 'Урок');
        }

        $subjects = $conversation->tutor?->tutorProfile?->subjects;

        return (string) ($subjects[0] ?? 'Общий чат');
    }

    private function hasActiveRequest(Conversation $conversation): bool
    {
        if (! $conversation->lesson) {
            return false;
        }

        return in_array($conversation->lesson->status, [\App\Models\Lesson::STATUS_PENDING, \App\Models\Lesson::STATUS_CONFIRMED], true);
    }

    private function reviewState(Conversation $conversation): ?array
    {
        $lesson = $conversation->lesson;
        $user = auth()->user();

        if (! $lesson || ! $user) {
            return null;
        }

        $canReview = in_array($user->id, array_filter([$lesson->student_id, $lesson->parent_id]), true)
            && $lesson->status === Lesson::STATUS_COMPLETED
            && $lesson->payment_status === Lesson::PAYMENT_PAID
            && $lesson->end_time->addHour()->isPast();

        if (! $canReview) {
            return null;
        }

        return [
            'submitted' => $lesson->feedback_submitted_at !== null,
            'rating' => $lesson->student_rating,
            'can_prompt' => $lesson->feedback_submitted_at === null,
            'show_upsell' => $lesson->feedback_submitted_at !== null
                && (int) $lesson->student_rating >= 4
                && $lesson->package_code === 'single',
            'upsell_url' => route('tutors.show', [
                'tutor' => $conversation->tutor?->tutorProfile,
                'date' => now(config('booking.display_timezone'))->addDay()->format('Y-m-d'),
            ]) . '#booking',
        ];
    }
}
