<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'minute_id',
    'policy_number',
    'type',
    'recipients',
    'copies',
    'subject',
    'body',
    'has_attachment',
    'status',
    'error_message',
    'sent_at',
])]
class EmailDelivery extends Model
{
    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'copies' => 'array',
            'has_attachment' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function minute(): BelongsTo
    {
        return $this->belongsTo(Minute::class);
    }
}
