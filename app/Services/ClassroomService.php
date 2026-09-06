<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassroomService
{
    public function __construct(
        private readonly ClassroomTokenIssuer $tokenIssuer
    ) {}

    public function openClassroom(Lesson $lesson, User $user): ClassroomSession
    {
        if (! $this->canAccess($lesson, $user)) {
            throw ValidationException::withMessages([
                'classroom' => 'У вас нет доступа к этому виртуальному классу.',
            ]);
        }

        $existing = ClassroomSession::query()
            ->where('lesson_id', $lesson->id)
            ->whereIn('status', [ClassroomSession::STATUS_WAITING, ClassroomSession::STATUS_ACTIVE])
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return ClassroomSession::query()->create([
            'lesson_id' => $lesson->id,
            'room_id' => (string) Str::uuid(),
            'status' => ClassroomSession::STATUS_WAITING,
        ]);
    }

    public function generateMediaToken(ClassroomSession $session, User $user): string
    {
        $session->loadMissing('lesson');

        return $this->tokenIssuer->issue($session->lesson, $user);
    }

    public function canAccess(Lesson $lesson, User $user): bool
    {
        if (! in_array($user->id, [$lesson->tutor_id, $lesson->student_id], true)) {
            return false;
        }

        // Проверка `role === 'parent'` была мёртвым кодом (enum никогда не
        // равен строке) и к тому же сломала бы родителя, бронирующего урок:
        // родитель-букер записывается как student_id, т.е. является
        // полноценным участником занятия.

        $hasActiveClassroom = ClassroomSession::query()
            ->where('lesson_id', $lesson->id)
            ->whereIn('status', [ClassroomSession::STATUS_WAITING, ClassroomSession::STATUS_ACTIVE])
            ->exists();

        return in_array($lesson->status, [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED], true) || $hasActiveClassroom;
    }

    public function endClassroom(ClassroomSession $session): void
    {
        $endedAt = now('UTC');
        $startedAt = $session->started_at ?? $session->created_at;
        $durationSeconds = $startedAt ? (int) $startedAt->diffInSeconds($endedAt) : null;

        $session->update([
            'status' => ClassroomSession::STATUS_ENDED,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
        ]);
    }

    public function saveWhiteboardState(ClassroomSession $session, array $state): void
    {
        $session->update([
            'whiteboard_state' => $state,
        ]);
    }

    public function getMediaServerUrl(): string
    {
        return (string) config('classroom.media_server_url');
    }

    public function getIceServers(): array
    {
        $servers = (array) config('classroom.ice_servers', []);

        $turnUrl = config('classroom.turn.url');

        if ($turnUrl !== null && $turnUrl !== '') {
            $servers[] = [
                'urls' => $turnUrl,
                'username' => (string) config('classroom.turn.username'),
                'credential' => (string) config('classroom.turn.credential'),
            ];
        }

        return $servers;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
