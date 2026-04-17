<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SLA extends Model
{
    protected $table = 'sla_definitions';

    protected $fillable = [
        'name',
        'description',
        'category',
        'priority',
        'first_response_minutes',
        'resolution_minutes',
        'warning_before_minutes',
        'auto_escalate',
        'escalation_priority',
        'is_active',
    ];

    protected $casts = [
        'auto_escalate' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'sla_definition_id');
    }
}