<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'company_name',
        'email',
        'phone',
        'whatsapp',
        'source',
        'lead_source',
        'status',
        'priority',
        'assigned_to',
        'last_whatsapp_message',
        'last_whatsapp_at',
        'lead_score',
        'lead_temperature',
        'lead_score_breakdown',
        'source_campaign',
        'conversation_id',
        'source_whatsapp_conversation_id',
        'notes',
    ];

    protected $casts = [
        'last_whatsapp_at' => 'datetime',
        'lead_score' => 'integer',
        'lead_score_breakdown' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sourceWhatsappConversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'source_whatsapp_conversation_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }
}
