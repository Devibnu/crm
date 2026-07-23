<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use HasFactory;

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * @return array<int, string>
     */
    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function activeOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    protected $fillable = [
        'name',
        'description',
        'priority',
        'response_time_minutes',
        'resolution_time_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeFilterPriority(Builder $query, string $priority, array $allowed): Builder
    {
        if (! in_array($priority, $allowed, true)) {
            return $query;
        }

        return $query->where('priority', $priority);
    }

    public function scopeFilterActive(Builder $query, string $active): Builder
    {
        if (! in_array($active, array_keys(self::activeOptions()), true)) {
            return $query;
        }

        return $query->where('is_active', $active === self::STATUS_ACTIVE);
    }
}
