<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ChatUnreadCounter
{
    public function countForUser(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return Message::query()
            ->when(
                $user->role !== 'admin',
                fn (Builder $query): Builder => $query->whereHas('conversation', function (Builder $inner) use ($user): Builder {
                    return $inner->where('tutor_id', $user->id)->orWhere('student_id', $user->id);
                })
            )
            ->where(function (Builder $query) use ($user): Builder {
                return $query->whereNull('sender_id')->orWhere('sender_id', '!=', $user->id);
            })
            ->where('is_read', false)
            ->count();
    }
}
