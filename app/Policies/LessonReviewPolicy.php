<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\User;

class LessonReviewPolicy
{
    /**
     * Only the student who took the lesson can create a review.
     */
    public function create(User $user, Lesson $lesson): bool
    {
        // Only the student (or parent who booked) can leave a review
        if (! in_array($user->id, array_filter([$lesson->student_id, $lesson->parent_id]), true)) {
            return false;
        }

        // Lesson must be completed and paid
        if ($lesson->status !== Lesson::STATUS_COMPLETED || $lesson->payment_status !== Lesson::PAYMENT_PAID) {
            return false;
        }

        // Can only review once
        return ! LessonReview::query()
            ->where('lesson_id', $lesson->id)
            ->where('reviewer_id', $user->id)
            ->exists();
    }

    /**
     * Only the reviewer can update their own review.
     */
    public function update(User $user, LessonReview $review): bool
    {
        return $user->id === $review->reviewer_id;
    }

    /**
     * Only admins can delete reviews.
     */
    public function delete(User $user, LessonReview $review): bool
    {
        return $user->role === 'admin';
    }
}
