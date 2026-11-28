<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Minute extends Model
{
    protected $fillable = [
        'folio',
        'source_service',
        'sae_policy_id',
        'client_name',
        'service_date',
        'start_time',
        'end_time',
        'elapsed_seconds',
        'hours_used',
        'policy_number',
        'contracted_hours',
        'available_before_hours',
        'available_hours',
        'validity',
        'adviser',
        'reporter',
        'recipient',
        'recipient_email',
        'service_status',
        'send_status',
        'sent_at',
        'issue',
        'work_performed',
        'pending_activity',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'elapsed_seconds' => 'integer',
            'hours_used' => 'decimal:2',
            'contracted_hours' => 'decimal:2',
            'available_before_hours' => 'decimal:2',
            'available_hours' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }

    public function emailDeliveries(): HasMany
    {
        return $this->hasMany(EmailDelivery::class);
    }
}
