<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ReferenceValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_type_id',
        'code',
        'label',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_active',
        'is_system',
        'is_default',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (ReferenceValue $value): void {
            if ($value->isDirty('code')) {
                throw ValidationException::withMessages([
                    'code' => 'Reference value code cannot be changed after creation.',
                ]);
            }
        });

        static::deleting(function (ReferenceValue $value): void {
            if ($value->is_system) {
                throw ValidationException::withMessages([
                    'reference_value' => 'System reference values cannot be deleted.',
                ]);
            }
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ReferenceType::class, 'reference_type_id');
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(ReferenceValueCapability::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    public function allowsCapability(string $capability): bool
    {
        if ($this->relationLoaded('capabilities')) {
            return $this->capabilities->contains('capability', $capability);
        }

        return $this->capabilities()->where('capability', $capability)->exists();
    }
}
