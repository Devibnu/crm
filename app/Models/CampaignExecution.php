<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_campaign_id',
        'audience_segment_id',
        'channel',
        'status',
        'execution_name',
        'scheduled_at',
        'started_at',
        'completed_at',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'response_count',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class);
    }

    public function audienceSegment(): BelongsTo
    {
        return $this->belongsTo(AudienceSegment::class);
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
                ->whereRaw('LOWER(execution_name) LIKE ?', [$term])
                ->orWhereHas('marketingCampaign', fn (Builder $campaignQuery) => $campaignQuery->whereRaw('LOWER(name) LIKE ?', [$term]))
                ->orWhereHas('audienceSegment', fn (Builder $segmentQuery) => $segmentQuery->whereRaw('LOWER(name) LIKE ?', [$term]));
        });
    }

    /**
     * @param array<int, string> $allowedChannels
     */
    public function scopeFilterChannel(Builder $query, ?string $channel, array $allowedChannels = []): Builder
    {
        if ($channel !== null && $channel !== '' && in_array($channel, $allowedChannels, true)) {
            $query->where('channel', $channel);
        }

        return $query;
    }

    /**
     * @param array<int, string> $allowedStatuses
     */
    public function scopeFilterStatus(Builder $query, ?string $status, array $allowedStatuses = []): Builder
    {
        if ($status !== null && $status !== '' && in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        return $query;
    }
}
