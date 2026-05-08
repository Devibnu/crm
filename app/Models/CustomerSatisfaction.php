<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSatisfaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'customer_id',
        'rating',
        'feedback',
        'survey_channel',
        'sentiment',
        'submitted_at',
        'follow_up_required',
        'follow_up_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'follow_up_required' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('feedback', 'like', "%{$search}%")
                ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                    $customerQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('ticket', function (Builder $ticketQuery) use ($search) {
                    $ticketQuery->where('ticket_number', 'like', "%{$search}%");
                });
        });
    }

    public function scopeFilterRating(Builder $query, string $rating): Builder
    {
        if (! in_array($rating, ['1', '2', '3', '4', '5'], true)) {
            return $query;
        }

        return $query->where('rating', (int) $rating);
    }

    public function scopeFilterValue(Builder $query, string $column, string $value, array $allowed): Builder
    {
        if (! in_array($value, $allowed, true)) {
            return $query;
        }

        return $query->where($column, $value);
    }

    public function scopeFilterFollowUp(Builder $query, string $followUp): Builder
    {
        if (! in_array($followUp, ['yes', 'no'], true)) {
            return $query;
        }

        return $query->where('follow_up_required', $followUp === 'yes');
    }
}
