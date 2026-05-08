<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'target_audience',
        'budget',
        'expected_leads',
        'actual_leads',
        'start_date',
        'end_date',
        'description',
        'created_by',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
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
                ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                ->orWhereRaw('LOWER(target_audience) LIKE ?', [$term]);
        });
    }

    /**
     * @param array<int, string> $allowedTypes
     */
    public function scopeFilterType(Builder $query, ?string $type, array $allowedTypes = []): Builder
    {
        if ($type !== null && $type !== '' && in_array($type, $allowedTypes, true)) {
            $query->where('type', $type);
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
