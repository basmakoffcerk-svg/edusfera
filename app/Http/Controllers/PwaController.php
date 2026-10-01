<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\PushSubscription;
use App\Services\ChatUnreadCounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaController extends Controller
{
    public function badgeCount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'count' => 0,
                'unread_messages' => 0,
                'today_lessons' => 0,
            ]);
        }

        $unreadMessages = app(ChatUnreadCounter::class)->countForUser($user);

        $todayLessons = Lesson::query()
            ->where(function ($query) use ($user): void {
                $query->where('tutor_id', $user->id)
                    ->orWhere('student_id', $user->id);
            })
            ->where('status', Lesson::STATUS_CONFIRMED)
            ->whereDate('start_time', today())
            ->count();

        $total = $unreadMessages + $todayLessons;

        return response()->json([
            'count' => $total,
            'unread_messages' => $unreadMessages,
            'today_lessons' => $todayLessons,
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['nullable', 'string'],
            'keys.auth' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $subscription = PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $request->input('endpoint'),
            ],
            [
                'public_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'content_encoding' => $request->input('contentEncoding', 'aesgcm'),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'id' => $subscription->id,
        ]);
    }
}
