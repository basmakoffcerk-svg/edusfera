<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\Message;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\ChatBypassAttemptNotification;
use App\Notifications\NewChatMessageNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChatService
{
    /**
     * @var array<int, string>
     */
    private array $blockedWords = [
        'дурак',
        'идиот',
        'тупой',
    ];

    /**
     * @var array<int, string>
     */
    private array $unlockableContactPatterns = [
        '/(?:\+?\s*375|8\s*0\s*(?:25|29|33|44))[\d\-\s\(\)]{5,}/u',
        '/\+?\d[\d\-\s\(\)]{8,}/u',
        '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/iu',
        '/(?:https?:\/\/)?(?:t\.me|telegram|tg|viber|wa\.me|whatsapp|instagram|inst|insta|vk\.com|vkontakte)/iu',
        '/(?:(?:плюс|ноль|нуль|один|два|три|четыре|пять|шесть|семь|восемь|девять)\s*){5,}/iu',
    ];

    /**
     * @var array<int, string>
     */
    private array $paymentPatterns = [
        '/(?<!\+)\b(?:\d[ -]?){13,19}\b/u',
        '/\bBY\d{2}[A-Z0-9]{6,}\b/iu',
        '/(?:номер\s+карты|карта|карточка|реквизиты|iban|сч[её]т|ерип|переведи|переведите|оплати\s+на|скинь\s+на\s+карту|скиньте\s+на\s+карту|беларусбанк|приорбанк|альфа[ -]?банк|бнб[ -]?банк)/iu',
    ];

    public function getOrCreateConversation(int $tutorId, int $studentId, ?int $lessonId = null): Conversation
    {
        return DB::transaction(function () use ($lessonId, $studentId, $tutorId): Conversation {
            $query = Conversation::query()
                ->where('tutor_id', $tutorId)
                ->where('student_id', $studentId);

            if ($lessonId !== null) {
                $query->where('lesson_id', $lessonId);
            } else {
                $query->whereNull('lesson_id');
            }

            $conversation = $query->lockForUpdate()->first();

            if ($conversation) {
                return $conversation;
            }

            return Conversation::query()->create([
                'lesson_id' => $lessonId,
                'tutor_id' => $tutorId,
                'student_id' => $studentId,
                'last_message_at' => now('UTC'),
            ]);
        });
    }

    public function sendMessage(int $conversationId, int $senderId, string $message): array
    {
        $warning = null;
        $message = trim($message);
        $originalMessage = $message;
        $hasUnlockableContactAttempt = $this->containsByPatterns($message, $this->unlockableContactPatterns);
        $hasPaymentAttempt = $this->containsByPatterns($message, $this->paymentPatterns);
        $contactsUnlocked = false;

        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => 'Сообщение не может быть пустым.',
            ]);
        }

        if (mb_strlen($message) > 500) {
            throw ValidationException::withMessages([
                'message' => 'Сообщение не должно превышать 500 символов.',
            ]);
        }

        if ($this->containsBlockedWords($message)) {
            throw ValidationException::withMessages([
                'message' => 'Сообщение содержит недопустимую лексику.',
            ]);
        }

        $createdMessage = DB::transaction(function () use ($conversationId, &$contactsUnlocked, $hasUnlockableContactAttempt, $hasPaymentAttempt, $message, $originalMessage, $senderId, &$warning): Message {
            $conversation = Conversation::query()
                ->with(['tutor', 'student'])
                ->lockForUpdate()
                ->findOrFail($conversationId);

            if (! in_array($senderId, [$conversation->tutor_id, $conversation->student_id], true)) {
                throw ValidationException::withMessages([
                    'message' => 'Вы не можете писать в этот диалог.',
                ]);
            }

            $contactsUnlocked = $this->hasUnlockedContacts($conversation);

            if ($hasUnlockableContactAttempt && ! $contactsUnlocked) {
                $message = $this->maskUnlockableContacts($message);
            }

            if ($hasPaymentAttempt) {
                $message = $this->maskPaymentData($message);
            }

            $createdMessage = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'message' => $message,
                'is_system' => false,
                'meta' => ($hasUnlockableContactAttempt && ! $contactsUnlocked) || $hasPaymentAttempt
                    ? [
                        'masked' => true,
                        'original_excerpt' => mb_strimwidth($originalMessage, 0, 120, '...'),
                    ]
                    : null,
                'is_read' => false,
            ]);

            if ($hasUnlockableContactAttempt && ! $contactsUnlocked) {
                $warning = '🛡️ В целях вашей безопасности обмен личными контактами и ссылками откроется автоматически сразу после бронирования первого занятия. Оплата через Edusfera гарантирует вам возврат средств, если урок не состоится.';

                Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => null,
                    'message' => $warning,
                    'is_system' => true,
                    'meta' => [
                        'variant' => 'contacts_locked',
                    ],
                    'is_read' => false,
                ]);

                if ($conversation->tutor_id === $senderId) {
                    $this->handleTutorBypassAttempt($conversation, $createdMessage);
                }
            }

            if ($hasPaymentAttempt) {
                $warning = '🛡️ Реквизиты для прямых переводов скрыты. Для вашей безопасности и действия гарантии возврата все оплаты проходят только через Edusfera.';

                Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => null,
                    'message' => $warning,
                    'is_system' => true,
                    'meta' => [
                        'variant' => 'payment_locked',
                    ],
                    'is_read' => false,
                ]);

                if ($conversation->tutor_id === $senderId) {
                    $this->handleTutorBypassAttempt($conversation, $createdMessage);
                }
            }

            $conversation->update([
                'last_message_at' => now('UTC'),
            ]);

            $recipient = $conversation->tutor_id === $senderId
                ? $conversation->student
                : $conversation->tutor;

            if ($recipient instanceof User) {
                try {
                    $recipient->notify(new NewChatMessageNotification($conversation->fresh(), $createdMessage));
                } catch (Throwable $exception) {
                    // Не блокируем чат, если очередь/Redis временно недоступны.
                    Log::warning('chat_notification_failed', [
                        'conversation_id' => $conversation->id,
                        'recipient_id' => $recipient->id,
                        'message_id' => $createdMessage->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            Log::info('chat_message_sent', [
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'recipient_id' => $recipient?->id,
                'message_id' => $createdMessage->id,
                'contacts_locked' => ! $contactsUnlocked,
            ]);

            return $createdMessage;
        });

        return [
            'message' => $createdMessage->load('sender'),
            'warning' => $warning,
        ];
    }

    public function markAsRead(int $userId, int $conversationId): void
    {
        $conversation = Conversation::query()->findOrFail($conversationId);

        if (! in_array($userId, [$conversation->tutor_id, $conversation->student_id], true)) {
            return;
        }

        Message::query()
            ->where('conversation_id', $conversationId)
            ->where(function ($query) use ($userId) {
                $query->whereNull('sender_id')->orWhere('sender_id', '!=', $userId);
            })
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now('UTC'),
            ]);
    }

    public function hasUnlockedContactsForConversation(Conversation $conversation): bool
    {
        return $this->hasUnlockedContacts($conversation);
    }

    private function containsBlockedWords(string $message): bool
    {
        $lower = mb_strtolower($message);

        foreach ($this->blockedWords as $word) {
            if (str_contains($lower, $word)) {
                return true;
            }
        }

        return false;
    }

    public function sanitizeMessageForDisplay(string $message): string
    {
        return $this->maskPaymentData($message);
    }

    private function hasUnlockedContacts(Conversation $conversation): bool
    {
        return Lesson::query()
            ->where('tutor_id', $conversation->tutor_id)
            ->where('student_id', $conversation->student_id)
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->exists();
    }

    private function containsByPatterns(string $message, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return true;
            }
        }

        return false;
    }

    private function maskUnlockableContacts(string $message): string
    {
        $message = preg_replace('/(?:\+?\s*375|8\s*0\s*(?:25|29|33|44))[\d\-\s\(\)]{5,}/u', '[контакты скрыты]', $message) ?? $message;
        $message = preg_replace('/\+?\d[\d\-\s\(\)]{8,}/u', '[контакты скрыты]', $message) ?? $message;
        $message = preg_replace('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/iu', '[контакты скрыты]', $message) ?? $message;
        $message = preg_replace('/(?:https?:\/\/)?(?:t\.me|telegram|tg|viber|wa\.me|whatsapp|instagram|inst|insta|vk\.com|vkontakte)[^\s]*/iu', '[контакты скрыты]', $message) ?? $message;
        $message = preg_replace('/(?:(?:плюс|ноль|нуль|один|два|три|четыре|пять|шесть|семь|восемь|девять)\s*){5,}/iu', '[контакты скрыты]', $message) ?? $message;

        return $message;
    }

    private function maskPaymentData(string $message): string
    {
        $message = preg_replace('/(?<!\+)\b(?:\d[ -]?){13,19}\b/u', '[платежные данные скрыты]', $message) ?? $message;
        $message = preg_replace('/\bBY\d{2}[A-Z0-9]{6,}\b/iu', '[платежные данные скрыты]', $message) ?? $message;
        $message = preg_replace('/(?:номер\s+карты|карта|карточка|реквизиты|iban|сч[её]т|ерип|переведи|переведите|оплати\s+на|скинь\s+на\s+карту|скиньте\s+на\s+карту|беларусбанк|приорбанк|альфа[ -]?банк|бнб[ -]?банк)/iu', '[платежные данные скрыты]', $message) ?? $message;

        return $message;
    }

    public function unlockContactsForLesson(Lesson $lesson): void
    {
        $conversation = $this->getOrCreateConversation(
            tutorId: $lesson->tutor_id,
            studentId: $lesson->student_id,
            lessonId: $lesson->id,
        );

        $exists = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('is_system', true)
            ->where('meta->variant', 'contacts_unlocked')
            ->exists();

        if ($exists) {
            return;
        }

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'message' => '🎉 Занятие оплачено! Контакты открыты. Успешного урока!',
            'is_system' => true,
            'meta' => [
                'variant' => 'contacts_unlocked',
            ],
            'is_read' => false,
        ]);

        $conversation->update([
            'last_message_at' => now('UTC'),
        ]);
    }

    private function handleTutorBypassAttempt(Conversation $conversation, Message $message): void
    {
        $profile = TutorProfile::query()
            ->where('user_id', $conversation->tutor_id)
            ->lockForUpdate()
            ->first();

        if (! $profile) {
            return;
        }

        $attempts = $profile->contact_bypass_attempts + 1;
        $payload = ['contact_bypass_attempts' => $attempts];

        if ($attempts >= 3) {
            $payload['search_penalized_until'] = now()->addDays(7);
        }

        $profile->update($payload);

        if ($attempts >= 3) {
            $tutor = $conversation->tutor()->first();

            if (! $tutor) {
                return;
            }

            User::query()
                ->where('role', 'admin')
                ->each(function (User $admin) use ($attempts, $conversation, $message, $tutor): void {
                    $admin->notify(new ChatBypassAttemptNotification($tutor, $conversation, $message, $attempts));
                });
        }
    }
}
