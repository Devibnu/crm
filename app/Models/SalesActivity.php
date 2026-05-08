<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'related_type',
        'related_id',
        'type',
        'subject',
        'description',
        'activity_at',
        'assigned_to',
        'outcome',
    ];

    protected $casts = [
        'activity_at' => 'datetime',
    ];

    public function relatedLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'related_id');
    }

    public function relatedOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'related_id');
    }

    public function relatedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'related_id');
    }

    public function getRelatedLabelAttribute(): string
    {
        return match ($this->related_type) {
            'lead' => $this->relatedLead?->name ?? 'Lead #'.$this->related_id,
            'opportunity' => $this->relatedOpportunity?->title ?? 'Opportunity #'.$this->related_id,
            'customer' => $this->relatedCustomer?->name ?? 'Customer #'.$this->related_id,
            default => 'Related #'.$this->related_id,
        };
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('assigned_to', 'like', "%{$search}%");
        });
    }
}
