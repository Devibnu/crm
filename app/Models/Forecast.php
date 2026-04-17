<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Forecast extends Model
{
    protected $fillable = [
        'period_label',
        'snapshot_date',
        'forecast_amount',
        'weighted_amount',
        'committed_amount',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'forecast_amount' => 'decimal:2',
        'weighted_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'metadata' => 'array',
    ];
}