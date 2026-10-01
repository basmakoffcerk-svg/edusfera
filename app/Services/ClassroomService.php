<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        if ($lesson->status === Lesson::STATUS_COMPLETED) {
            $lastEnded = ClassroomSession::query()
                ->where('lesson_id', $lesson->id)
                ->latest('id')
                ->first();
            if ($lastEnded) {
                return $lastEnded;
            }
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
        if ($user->isAdmin()) {
            return true;
        }

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

        // 1. Metered Cloud TURN integration (if API key provided)
        $meteredApp = (string) config('classroom.metered.app_name');
        $meteredKey = (string) config('classroom.metered.api_key');
        if ($meteredApp !== '' && $meteredKey !== '') {
            try {
                $meteredServers = Cache::remember(
                    'classroom:metered_ice:'.$meteredApp,
                    43200, // 12 hours
                    function () use ($meteredApp, $meteredKey) {
                        $res = Http::timeout(3)
                            ->get("https://{$meteredApp}.metered.live/api/v1/turn/credentials?apiKey={$meteredKey}");
                        if ($res->successful() && is_array($res->json())) {
                            return $res->json();
                        }

                        return null;
                    }
                );
                if (is_array($meteredServers)) {
                    $servers = array_merge($servers, $meteredServers);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch Metered TURN credentials: '.$e->getMessage());
            }
        }

        // 2. Custom Coturn integration (ONLY if explicitly set in .env)
        $turnUrl = (string) config('classroom.turn.url');
        if ($turnUrl !== '') {
            $urls = [$turnUrl];
            if (! str_contains($turnUrl, 'transport=')) {
                $separator = str_contains($turnUrl, '?') ? '&' : '?';
                $urls[] = $turnUrl.$separator.'transport=tcp';
            }
            if (str_contains($turnUrl, ':3478')) {
                $secureUrl = str_replace(':3478', ':5349', $turnUrl);
                $secureUrl = preg_replace('/\?.*$/', '', $secureUrl);
                $urls[] = str_replace('turn:', 'turns:', $secureUrl).'?transport=tcp';
            }

            $servers[] = [
                'urls' => $urls,
                'username' => (string) config('classroom.turn.username', 'edusfera'),
                'credential' => (string) config('classroom.turn.credential', 'change-me-strong-password'),
            ];
        }

        // 3. Always include verified OpenRelay TURN servers on ports 80 & 443 (TCP & UDP)
        // Tested and verified open worldwide for bypassing cellular CGNAT (4G/LTE)
        $servers[] = [
            'urls' => [
                'turn:openrelay.metered.ca:80',
                'turn:openrelay.metered.ca:443',
                'turn:openrelay.metered.ca:443?transport=tcp',
                'turns:openrelay.metered.ca:443?transport=tcp',
            ],
            'username' => 'openrelayproject',
            'credential' => 'openrelayproject',
        ];

        return $servers;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
