<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaePolicy extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'client_code', 'policy_number', 'policy_date', 'contracted_hours', 'available_hours'];

    protected function casts(): array
    {
        return [
            'policy_date' => 'date',
            'contracted_hours' => 'decimal:2',
            'available_hours' => 'decimal:2',
        ];
    }
}
