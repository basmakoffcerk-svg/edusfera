<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, UserRole::allPanelRoles(), true);
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $this->ownsLesson($user, $lesson);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Student, UserRole::Parent], true);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $this->ownsLesson($user, $lesson);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->role === UserRole::Admin;
    }

    private function ownsLesson(User $user, Lesson $lesson): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Tutor) {
            return $lesson->tutor_id === $user->id;
        }

        if ($user->role === UserRole::Student) {
            return $lesson->student_id === $user->id;
        }

        if ($user->role === UserRole::Parent) {
            return $lesson->parent_id === $user->id;
        }

        return false;
    }
}
