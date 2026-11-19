<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source_service',
    'user_id',
    'started_at',
    'ended_at',
    'elapsed_seconds',
    'status',
])]
class ServiceSession extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'elapsed_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function frontendProfile(): array
    {
        return [
            'id' => $this->id,
            'sourceService' => $this->source_service,
            'status' => $this->status,
            'startedAt' => $this->started_at?->toIso8601String(),
            'endedAt' => $this->ended_at?->toIso8601String(),
            'elapsedSeconds' => $this->status === 'running' && $this->started_at
                ? max(0, (int) $this->started_at->diffInSeconds(now()))
                : (int) $this->elapsed_seconds,
            'startedBy' => $this->user?->name,
        ];
    }
}
