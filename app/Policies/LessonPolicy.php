<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'tutor', 'student', 'parent'], true);
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $this->ownsLesson($user, $lesson);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'student', 'parent'], true);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $this->ownsLesson($user, $lesson);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->role === 'admin';
    }

    private function ownsLesson(User $user, Lesson $lesson): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'tutor') {
            return $lesson->tutor_id === $user->id;
        }

        if ($user->role === 'student') {
            return $lesson->student_id === $user->id;
        }

        if ($user->role === 'parent') {
            return $lesson->parent_id === $user->id || $lesson->student_id === $user->id;
        }

        return false;
    }
}
