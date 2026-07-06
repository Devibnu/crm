<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id',
        'lead_id',
        'customer_id',
        'conversation_id',
        'quote_number',
        'title',
        'amount',
        'status',
        'valid_until',
        'issued_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'valid_until' => 'date',
        'issued_at' => 'date',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }
}
