<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ClassroomFile;
use App\Models\User;

class ClassroomFilePolicy
{
    public function view(User $user, ClassroomFile $file): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $session = $file->classroomSession;

        if (! $session || ! $session->lesson) {
            return false;
        }

        $lesson = $session->lesson;

        return $lesson->tutor_id === $user->id
            || $lesson->student_id === $user->id
            || $lesson->parent_id === $user->id;
    }

    public function download(User $user, ClassroomFile $file): bool
    {
        return $this->view($user, $file);
    }

    public function upload(User $user, ClassroomFile $file): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $session = $file->classroomSession;

        if (! $session || ! $session->lesson) {
            return false;
        }

        $lesson = $session->lesson;

        return $lesson->tutor_id === $user->id || $lesson->student_id === $user->id;
    }

    public function delete(User $user, ClassroomFile $file): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        $session = $file->classroomSession;
        $tutorId = $session?->lesson?->tutor_id;

        // Only the uploader or tutor can delete
        return $file->uploaded_by === $user->id || ($tutorId !== null && $tutorId === $user->id);
    }
}
