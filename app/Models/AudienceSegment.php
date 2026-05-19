<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudienceSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'criteria',
        'estimated_audience',
        'status',
        'created_by',
    ];

    protected $casts = [
        'criteria' => 'array',
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
                ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
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
