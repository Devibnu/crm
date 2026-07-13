<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'whatsapp_conversation_id',
        'customer_id',
        'lead_id',
        'ticket_id',
        'phone',
        'direction',
        'message_type',
        'message',
        'status',
        'provider_message_id',
        'provider',
        'broadcast_id',
        'sent_at',
        'received_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
        'raw_payload',
        'media_path',
        'media_original_name',
        'media_mime',
        'media_size',
        'media_id',
        'media_url',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'raw_payload' => 'array',
        'media_size' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function isTemplateMessage(): bool
    {
        return $this->message_type === 'template'
            || filled($this->templateName());
    }

    public function templateName(): ?string
    {
        $payload = $this->raw_payload ?: [];
        $name = data_get($payload, 'template_name')
            ?? data_get($payload, 'template.name')
            ?? data_get($payload, 'request.template.name');

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }
}
