<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'customer_id',
        'conversation_id',
        'title',
        'company_name',
        'contact_name',
        'estimated_value',
        'probability',
        'status',
        'won_at',
        'lost_at',
        'lost_reason',
        'expected_close_date',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'expected_close_date' => 'date',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class)->latestOfMany();
    }
}
