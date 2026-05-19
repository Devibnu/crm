<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingAutomation extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_campaign_id',
        'audience_segment_id',
        'name',
        'trigger_type',
        'action_type',
        'status',
        'delay_minutes',
        'conditions',
        'action_payload',
        'executed_count',
        'last_executed_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'conditions' => 'array',
        'action_payload' => 'array',
        'last_executed_at' => 'datetime',
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
                ->whereRaw('LOWER(name) LIKE ?', [$term])
                ->orWhereRaw('LOWER(notes) LIKE ?', [$term]);
        });
    }

    public function scopeFilterTrigger(Builder $query, ?string $trigger, array $allowedTriggers = []): Builder
    {
        if ($trigger !== null && $trigger !== '' && in_array($trigger, $allowedTriggers, true)) {
            $query->where('trigger_type', $trigger);
        }

        return $query;
    }

    public function scopeFilterAction(Builder $query, ?string $action, array $allowedActions = []): Builder
    {
        if ($action !== null && $action !== '' && in_array($action, $allowedActions, true)) {
            $query->where('action_type', $action);
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
