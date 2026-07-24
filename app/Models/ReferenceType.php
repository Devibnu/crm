<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ReferenceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'governance_level',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'governance_level' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (ReferenceType $type): void {
            if ($type->isDirty('code')) {
                throw ValidationException::withMessages([
                    'code' => 'Reference type code cannot be changed after creation.',
                ]);
            }
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(ReferenceValue::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
