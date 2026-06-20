<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassroomSession extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'lesson_id',
        'room_id',
        'status',
        'started_at',
        'ended_at',
        'duration_seconds',
        'whiteboard_state',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'whiteboard_state' => 'array',
            'meta' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ClassroomNote::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ClassroomFile::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ClassroomChatMessage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    public function scopeEnded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ENDED);
    }
}
