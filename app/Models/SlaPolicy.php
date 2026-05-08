<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use HasFactory;

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
        if (! in_array($active, ['active', 'inactive'], true)) {
            return $query;
        }

        return $query->where('is_active', $active === 'active');
    }
}
