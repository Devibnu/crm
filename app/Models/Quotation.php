<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    protected $fillable = [
        'quote_number',
        'opportunity_id',
        'title',
        'amount',
        'currency',
        'valid_until',
        'status',
        'approval_notes',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'valid_until' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}