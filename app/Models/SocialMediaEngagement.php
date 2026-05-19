<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMediaEngagement extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_campaign_id',
        'platform',
        'post_title',
        'content',
        'post_url',
        'status',
        'posted_at',
        'likes_count',
        'comments_count',
        'shares_count',
        'impressions_count',
        'engagement_rate',
        'created_by',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'engagement_rate' => 'decimal:2',
    ];

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class);
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
                ->whereRaw('LOWER(post_title) LIKE ?', [$term])
                ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
        });
    }

    public function scopeFilterPlatform(Builder $query, ?string $platform, array $allowedPlatforms = []): Builder
    {
        if ($platform !== null && $platform !== '' && in_array($platform, $allowedPlatforms, true)) {
            $query->where('platform', $platform);
        }

        return $query;
    }

    public function scopeFilterStatus(Builder $query, ?string $status, array $allowedStatuses = []): Builder
    {
        if ($status !== null && $status !== '' && in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        return $query;
    }
}
