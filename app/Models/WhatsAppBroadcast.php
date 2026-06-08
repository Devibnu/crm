<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppBroadcast extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcasts';

    protected $fillable = [
        'marketing_campaign_id',
        'name',
        'message_template',
        'send_mode',
        'whatsapp_message_template_id',
        'template_variable_defaults',
        'target_type',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'total_sent',
        'delivered_count',
        'read_count',
        'replied_count',
        'failed_count',
        'total_failed',
        'delivery_rate',
        'reply_rate',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivery_rate' => 'decimal:2',
        'reply_rate' => 'decimal:2',
        'template_variable_defaults' => 'array',
    ];

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppBroadcastRecipient::class, 'whatsapp_broadcast_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(WhatsAppBroadcastReply::class, 'whatsapp_broadcast_id');
    }

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessageTemplate::class, 'whatsapp_message_template_id');
    }

    public function refreshDeliveryStats(): void
    {
        $totals = $this->recipients()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ('sent','delivered','read','replied') THEN 1 ELSE 0 END) as sent_total")
            ->selectRaw("SUM(CASE WHEN status IN ('delivered','read','replied') THEN 1 ELSE 0 END) as delivered_total")
            ->selectRaw("SUM(CASE WHEN status IN ('read','replied') THEN 1 ELSE 0 END) as read_total")
            ->selectRaw("SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_total")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_total")
            ->first();

        $total = (int) ($totals->total ?? 0);
        $sent = (int) ($totals->sent_total ?? 0);
        $delivered = (int) ($totals->delivered_total ?? 0);
        $replied = (int) ($totals->replied_total ?? 0);
        $failed = (int) ($totals->failed_total ?? 0);

        $this->forceFill([
            'total_recipients' => $total,
            'sent_count' => $sent,
            'total_sent' => $sent,
            'delivered_count' => $delivered,
            'read_count' => (int) ($totals->read_total ?? 0),
            'replied_count' => $replied,
            'failed_count' => $failed,
            'total_failed' => $failed,
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 2) : 0,
            'reply_rate' => $total > 0 ? round(($replied / $total) * 100, 2) : 0,
        ])->save();
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
                ->whereRaw('LOWER(name) LIKE ?', [$term])
                ->orWhereRaw('LOWER(message_template) LIKE ?', [$term])
                ->orWhereRaw('LOWER(created_by) LIKE ?', [$term])
                ->orWhereHas('marketingCampaign', fn (Builder $campaignQuery) => $campaignQuery->whereRaw('LOWER(name) LIKE ?', [$term]));
        });
    }

    /**
     * @param array<int, string> $allowed
     */
    public function scopeFilterStatus(Builder $query, ?string $status, array $allowed = []): Builder
    {
        if ($status !== null && $status !== '' && in_array($status, $allowed, true)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * @param array<int, string> $allowed
     */
    public function scopeFilterTargetType(Builder $query, ?string $targetType, array $allowed = []): Builder
    {
        if ($targetType !== null && $targetType !== '' && in_array($targetType, $allowed, true)) {
            $query->where('target_type', $targetType);
        }

        return $query;
    }
}
