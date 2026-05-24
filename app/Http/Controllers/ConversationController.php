<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Services\ChatService;
use Illuminate\Http\RedirectResponse;

class ConversationController extends Controller
{
    public function startWithTutor(TutorProfile $tutor, ChatService $chatService): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && in_array($user->role, ['student', 'parent'], true), 403);

        $conversation = $chatService->getOrCreateConversation(
            tutorId: $tutor->user_id,
            studentId: $user->id,
        );

        return redirect("/admin/messages?conversation={$conversation->id}");
    }

    public function startFromLesson(Lesson $lesson, ChatService $chatService): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        if (! in_array($user->id, array_filter([$lesson->tutor_id, $lesson->student_id, $lesson->parent_id]), true)) {
            abort(403);
        }

        $conversation = $chatService->getOrCreateConversation(
            tutorId: $lesson->tutor_id,
            studentId: $lesson->student_id,
            lessonId: $lesson->id,
        );

        return redirect("/admin/messages?conversation={$conversation->id}");
    }
}
