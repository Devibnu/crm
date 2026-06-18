<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBroadcastReply extends Model
{
    use HasFactory;

    public const REPLY_TYPES = ['lead', 'support', 'general', 'unsubscribe'];
    public const SENTIMENTS = ['positive', 'neutral', 'negative'];
    public const ACTION_STATUSES = ['new_lead', 'follow_up_sales', 'send_to_omnichannel', 'closed', 'opt_out'];

    protected $table = 'whatsapp_broadcast_replies';

    protected $fillable = [
        'whatsapp_broadcast_id',
        'whatsapp_broadcast_recipient_id',
        'lead_id',
        'ticket_id',
        'sender_name',
        'phone_number',
        'message',
        'reply_type',
        'sentiment',
        'action_status',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $reply) {
            $classification = self::classifyMessage((string) $reply->message);

            $reply->reply_type = $reply->reply_type ?: $classification['reply_type'];
            $reply->sentiment = $reply->sentiment ?: $classification['sentiment'];
            $reply->action_status = $reply->action_status ?: $classification['action_status'];
        });
    }

    /**
     * @return array{reply_type: string, sentiment: string, action_status: string}
     */
    public static function classifyMessage(string $message): array
    {
        $text = mb_strtolower($message);

        $replyType = 'general';

        if (self::containsAny($text, ['stop', 'berhenti', 'unsubscribe', 'jangan kirim lagi'])) {
            $replyType = 'unsubscribe';
        } elseif (self::containsAny($text, ['saya tertarik', 'hubungi saya', 'berapa harga', 'minta penawaran'])) {
            $replyType = 'lead';
        } elseif (self::containsAny($text, ['komplain', 'invoice', 'tiket', 'masalah', 'kendala'])) {
            $replyType = 'support';
        }

        $sentiment = match ($replyType) {
            'lead' => 'positive',
            'support', 'unsubscribe' => 'negative',
            default => self::containsAny($text, ['terima kasih', 'thanks', 'ok', 'setuju', 'bagus'])
                ? 'positive'
                : (self::containsAny($text, ['kecewa', 'marah', 'buruk', 'jelek']) ? 'negative' : 'neutral'),
        };

        $actionStatus = match ($replyType) {
            'lead' => 'new_lead',
            'support' => 'send_to_omnichannel',
            'unsubscribe' => 'opt_out',
            default => 'closed',
        };

        return [
            'reply_type' => $replyType,
            'sentiment' => $sentiment,
            'action_status' => $actionStatus,
        ];
    }

    protected static function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{reply_type: string, sentiment: string, action_status: string}
     */
    public function resolvedClassification(): array
    {
        $classification = self::classifyMessage((string) $this->message);

        return [
            'reply_type' => $this->reply_type ?: $classification['reply_type'],
            'sentiment' => $this->sentiment ?: $classification['sentiment'],
            'action_status' => $this->action_status ?: $classification['action_status'],
        ];
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
