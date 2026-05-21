<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OmnichannelMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'lead_id',
        'channel',
        'direction',
        'sender_name',
        'sender_contact',
        'subject',
        'message',
        'status',
        'assigned_to',
        'received_at',
        'resolved_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('sender_name', 'like', "%{$search}%")
                ->orWhere('sender_contact', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('message', 'like', "%{$search}%");
        });
    }

    public function scopeFilterChannel(Builder $query, string $channel, array $allowed): Builder
    {
        if (! in_array($channel, $allowed, true)) {
            return $query;
        }

        return $query->where('channel', $channel);
    }

    public function scopeFilterStatus(Builder $query, string $status, array $allowed): Builder
    {
        if (! in_array($status, $allowed, true)) {
            return $query;
        }

        return $query->where('status', $status);
    }
}
