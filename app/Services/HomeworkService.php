<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HomeworkAssignment;
use Illuminate\Validation\ValidationException;

class HomeworkService
{
    public function markCompleted(HomeworkAssignment $assignment, int $actorId): HomeworkAssignment
    {
        if ($assignment->student_id !== $actorId) {
            throw ValidationException::withMessages([
                'homework' => 'Это домашнее задание недоступно для текущего пользователя.',
            ]);
        }

        if ($assignment->status === 'completed') {
            return $assignment;
        }

        $assignment->update([
            'status' => 'completed',
            'completed_at' => now('UTC'),
        ]);

        return $assignment->fresh();
    }
}
