<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    /**
     * @return array<int, string>
     */
    public static function statusOptions(): array
    {
        return ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed', 'reopened'];
    }

    /**
     * @return array<int, string>
     */
    public static function priorityOptions(): array
    {
        return ['low', 'medium', 'high', 'urgent'];
    }

    /**
     * @return array<int, string>
     */
    public static function channelOptions(): array
    {
        return ['email', 'phone', 'whatsapp', 'web', 'social', 'walk_in'];
    }

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'lead_id',
        'whatsapp_message_id',
        'whatsapp_broadcast_reply_id',
        'conversation_id',
        'source_type',
        'source_id',
        'subject',
        'description',
        'priority',
        'status',
        'channel',
        'assigned_to',
        'due_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class);
    }

    public function whatsappBroadcastReply(): BelongsTo
    {
        return $this->belongsTo(WhatsAppBroadcastReply::class);
    }

    public function sourceConversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('ticket_number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('assigned_to', 'like', "%{$search}%")
                ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                    $customerQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    public function scopeFilter(Builder $query, string $column, string $value, array $allowed): Builder
    {
        if (! in_array($value, $allowed, true)) {
            return $query;
        }

        return $query->where($column, $value);
    }
}
