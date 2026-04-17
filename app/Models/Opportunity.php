<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    protected $fillable = [
        'code',
        'lead_id',
        'assigned_user_id',
        'name',
        'stage',
        'amount',
        'currency',
        'probability',
        'expected_close_date',
        'status_notes',
        'closed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}