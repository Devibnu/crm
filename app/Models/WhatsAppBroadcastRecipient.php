<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBroadcastRecipient extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcast_recipients';

    protected $fillable = [
        'whatsapp_broadcast_id',
        'recipient_type',
        'recipient_id',
        'recipient_name',
        'phone_number',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'replied_at',
        'provider_message_id',
        'error_message',
        'failed_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WhatsAppBroadcast::class, 'whatsapp_broadcast_id');
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
                ->whereRaw('LOWER(recipient_name) LIKE ?', [$term])
                ->orWhereRaw('LOWER(phone_number) LIKE ?', [$term]);
        });
    }
}
