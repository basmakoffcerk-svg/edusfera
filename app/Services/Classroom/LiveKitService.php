<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Models\Lesson;
use App\Models\User;
use Firebase\JWT\JWT;

class LiveKitService
{
    public function isConfigured(): bool
    {
        $key = (string) config('classroom.livekit_api_key');
        $secret = (string) config('classroom.livekit_api_secret');

        if (empty($key) || empty($secret) || $key === 'APIhSge7UhaQGJ4' || str_starts_with($key, 'your_')) {
            return false;
        }

        return true;
    }

    public function generateToken(Lesson $lesson, User $user, int $ttlSeconds = 7200): string
    {
        $apiKey = (string) config('classroom.livekit_api_key');
        $apiSecret = (string) config('classroom.livekit_api_secret');

        $now = time();
        $isTutor = ($user->id === $lesson->tutor_id) || $user->isAdmin();
        $identity = $isTutor ? 'tutor_'.$user->id : 'student_'.$user->id;
        $roomName = 'edusfera_lesson_'.$lesson->id;

        $payload = [
            'iss' => $apiKey,
            'sub' => $identity,
            'name' => $user->name ?? ($isTutor ? 'Преподаватель' : 'Ученик'),
            'nbf' => $now - 5,
            'exp' => $now + $ttlSeconds,
            'video' => [
                'room' => $roomName,
                'roomJoin' => true,
                'canPublish' => true,
                'canSubscribe' => true,
                'canPublishData' => true,
                'roomAdmin' => $isTutor,
            ],
            'metadata' => json_encode([
                'lesson_id' => $lesson->id,
                'user_id' => $user->id,
                'role' => $isTutor ? 'tutor' : 'student',
            ]),
        ];

        return JWT::encode($payload, $apiSecret, 'HS256', null, ['alg' => 'HS256']);
    }

    public function getWsUrl(): string
    {
        return (string) config('classroom.livekit_host', 'wss://edusfera.livekit.cloud');
    }
}
