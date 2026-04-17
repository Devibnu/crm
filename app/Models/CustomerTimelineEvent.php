<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTimelineEvent extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'event_type',
        'title',
        'description',
        'meta',
        'event_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'event_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'customer_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function record(
        Pelanggan $customer,
        string $eventType,
        ?User $user,
        string $title,
        ?string $description = null,
        array $meta = [],
    ): self {
        return self::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'meta' => $meta ?: null,
            'event_at' => now(),
        ]);
    }
}