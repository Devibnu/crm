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
        'target_type',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'read_count',
        'replied_count',
        'failed_count',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
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
