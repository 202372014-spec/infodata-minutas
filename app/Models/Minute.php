<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Minute extends Model
{
    protected $fillable = [
        'folio',
        'source_service',
        'client_name',
        'service_date',
        'start_time',
        'end_time',
        'hours_used',
        'policy_number',
        'contracted_hours',
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
            'hours_used' => 'decimal:2',
            'contracted_hours' => 'decimal:2',
            'available_hours' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }
}
