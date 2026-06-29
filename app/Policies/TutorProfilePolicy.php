<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TutorProfile;
use App\Models\User;

class TutorProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['tutor', 'admin'], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TutorProfile $tutorProfile): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'tutor' && $tutorProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'tutor' && ! $user->tutorProfile()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TutorProfile $tutorProfile): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'tutor' && $tutorProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TutorProfile $tutorProfile): bool
    {
        return $user->role === 'admin';
    }
}
