<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class WhatsAppConversation extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'customer_id',
        'lead_id',
        'contact_name',
        'phone_number',
        'channel',
        'last_message',
        'last_message_at',
        'unread_count',
        'status',
        'priority',
        'assigned_to',
        'tags',
        'notes',
        'taken_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'tags' => 'array',
        'taken_at' => 'datetime',
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

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_conversation_id');
    }

    public function latestInboundMessage(): HasOne
    {
        return $this->hasOne(WhatsAppMessage::class, 'whatsapp_conversation_id')
            ->where('direction', 'inbound')
            ->latestOfMany();
    }

    public function lastInboundAt(): ?Carbon
    {
        if ($this->relationLoaded('latestInboundMessage')) {
            $message = $this->latestInboundMessage;

            return $message?->received_at ?? $message?->created_at;
        }

        $message = $this->messages()
            ->where('direction', 'inbound')
            ->latest('received_at')
            ->latest()
            ->first(['received_at', 'created_at']);

        return $message?->received_at ?? $message?->created_at;
    }

    public function whatsappSessionExpiresAt(): ?Carbon
    {
        return $this->lastInboundAt()?->copy()->addHours(24);
    }

    public function isWhatsAppSessionOpen(): bool
    {
        $expiresAt = $this->whatsappSessionExpiresAt();

        return $expiresAt !== null && now()->lt($expiresAt);
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(ConversationNote::class, 'conversation_id');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('contact_name', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhere('last_message', 'like', "%{$search}%");
        });
    }
}
