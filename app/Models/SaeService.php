<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaeService extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'policy_id', 'source_folio', 'source_date', 'client_name', 'adviser', 'reporter',
        'recipient', 'recipient_email', 'issue', 'work', 'pending', 'service_status', 'enabled',
    ];

    protected function casts(): array
    {
        return ['source_date' => 'date', 'enabled' => 'boolean'];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(SaePolicy::class);
    }

    public function minute(): HasOne
    {
        return $this->hasOne(Minute::class, 'source_service');
    }

    public function session(): HasOne
    {
        return $this->hasOne(ServiceSession::class, 'source_service');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('enabled', true)->whereDoesntHave('minute');
    }

    public function frontendProfile(): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->source_folio,
            'sourceDate' => $this->source_date?->format('Y-m-d'),
            'client' => $this->client_name,
            'policy' => $this->policy->policy_number,
            'contracted' => (float) $this->policy->contracted_hours,
            'available' => (float) $this->policy->available_hours,
            'validity' => 'Sin vigencia registrada',
            'adviser' => $this->adviser ?? '',
            'reporter' => $this->reporter ?? '',
            'recipient' => $this->recipient ?? '',
            'email' => $this->recipient_email ?? '',
            'issue' => $this->issue ?? '',
            'work' => $this->work ?? '',
            'pending' => $this->pending ?? '',
            'serviceStatus' => $this->service_status,
        ];
    }
}
