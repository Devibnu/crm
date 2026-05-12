<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBroadcastReply extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcast_replies';

    protected $fillable = [
        'whatsapp_broadcast_id',
        'whatsapp_broadcast_recipient_id',
        'sender_name',
        'phone_number',
        'message',
        'status',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WhatsAppBroadcast::class, 'whatsapp_broadcast_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(WhatsAppBroadcastRecipient::class, 'whatsapp_broadcast_recipient_id');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $term = '%' . mb_strtolower($search) . '%';

        return $query->where(function (Builder $innerQuery) use ($term) {
            $innerQuery
                ->whereRaw('LOWER(sender_name) LIKE ?', [$term])
                ->orWhereRaw('LOWER(phone_number) LIKE ?', [$term])
                ->orWhereRaw('LOWER(message) LIKE ?', [$term]);
        });
    }
}
