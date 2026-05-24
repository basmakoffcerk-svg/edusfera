<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Contracts\View\View;

class VirtualClassroomController extends Controller
{
    public function show(Lesson $lesson): View
    {
        $user = auth()->user();

        abort_unless($user !== null, 403);
        abort_unless(
            in_array($user->id, array_filter([$lesson->student_id, $lesson->parent_id, $lesson->tutor_id]), true),
            403
        );
        abort_unless($lesson->payment_status === Lesson::PAYMENT_PAID, 403);

        $roomName = 'edusfera-lesson-' . $lesson->id;
        $meetingUrl = 'https://meet.jit.si/' . urlencode($roomName);

        if (blank($lesson->meeting_link) || ! str_contains((string) $lesson->meeting_link, '/virtual-class/')) {
            $lesson->update([
                'meeting_link' => route('virtual.class', $lesson),
            ]);
        }

        return view('virtual-class.show', [
            'lesson' => $lesson->fresh(['tutor.tutorProfile', 'student']),
            'meetingUrl' => $meetingUrl,
        ]);
    }
}
