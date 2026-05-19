<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadScoringRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'trigger_source',
        'score_value',
        'routing_team',
        'routing_user',
        'conditions',
        'priority',
        'status',
        'auto_assign',
        'execution_count',
        'last_executed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'auto_assign' => 'boolean',
        'last_executed_at' => 'datetime',
    ];

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
            $query->where('trigger_source', $trigger);
        }

        return $query;
    }

    public function scopeFilterPriority(Builder $query, ?string $priority, array $allowedPriorities = []): Builder
    {
        if ($priority !== null && $priority !== '' && in_array($priority, $allowedPriorities, true)) {
            $query->where('priority', $priority);
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
