<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'policy_number',
    'client_name',
    'contracted_hours',
    'remaining_hours',
    'remaining_percentage',
    'threshold_percentage',
    'recipients',
    'status',
    'sent_at',
    'error_message',
])]
class PolicyAlert extends Model
{
    protected function casts(): array
    {
        return [
            'contracted_hours' => 'decimal:2',
            'remaining_hours' => 'decimal:2',
            'remaining_percentage' => 'decimal:2',
            'threshold_percentage' => 'integer',
            'recipients' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
