<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassroomNote;
use App\Models\User;

class ClassroomNotePolicy
{
    public function view(User $user, ClassroomNote $note): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $session = $note->classroomSession;

        if (! $session || ! $session->lesson) {
            return false;
        }

        $lesson = $session->lesson;

        // Shared notes visible to all lesson participants
        if ($note->is_shared) {
            return $lesson->tutor_id === $user->id
                || $lesson->student_id === $user->id
                || $lesson->parent_id === $user->id;
        }

        // Private notes visible only to author and tutor
        return $note->author_id === $user->id || $lesson->tutor_id === $user->id;
    }

    public function create(User $user, ClassroomNote $note): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $session = $note->classroomSession;

        if (! $session || ! $session->lesson) {
            return false;
        }

        $lesson = $session->lesson;

        return $lesson->tutor_id === $user->id || $lesson->student_id === $user->id;
    }

    public function delete(User $user, ClassroomNote $note): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Author can delete their own notes; tutor can delete any
        return $note->author_id === $user->id;
    }
}
