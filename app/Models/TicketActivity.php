<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'activity_type',
        'title',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        Ticket $ticket,
        string $activityType,
        ?User $user,
        string $title,
        ?string $description = null,
        array $metadata = [],
    ): self {
        $ticket->forceFill([
            'last_activity_at' => now(),
        ])->save();

        return self::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'activity_type' => $activityType,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }
}