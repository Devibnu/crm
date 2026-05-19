<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'visibility',
        'content',
        'tags',
        'author_name',
        'published_at',
        'is_published',
        'views_count',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('tags', 'like', "%{$search}%");
        });
    }

    public function scopeFilterCategory(Builder $query, string $category): Builder
    {
        if ($category === '') {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function scopeFilterVisibility(Builder $query, string $visibility): Builder
    {
        if (! in_array($visibility, ['public', 'internal'], true)) {
            return $query;
        }

        return $query->where('visibility', $visibility);
    }
}
