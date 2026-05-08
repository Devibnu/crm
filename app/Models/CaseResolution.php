<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseResolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'resolution_summary',
        'resolution_notes',
        'root_cause',
        'resolution_type',
        'resolved_by',
        'resolved_at',
        'customer_notified',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'customer_notified' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('resolution_summary', 'like', "%{$search}%")
                ->orWhere('resolved_by', 'like', "%{$search}%")
                ->orWhereHas('ticket', function (Builder $ticketQuery) use ($search) {
                    $ticketQuery
                        ->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
        });
    }

    public function scopeFilterResolutionType(Builder $query, string $type, array $allowed): Builder
    {
        if (! in_array($type, $allowed, true)) {
            return $query;
        }

        return $query->where('resolution_type', $type);
    }

    public function scopeFilterCustomerNotified(Builder $query, string $notified): Builder
    {
        if (! in_array($notified, ['yes', 'no'], true)) {
            return $query;
        }

        return $query->where('customer_notified', $notified === 'yes');
    }
}
