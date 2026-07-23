<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSlaEscalation extends Model
{
    use HasFactory;

    public const TYPE_RESPONSE_WARNING = 'response_warning';
    public const TYPE_RESPONSE_BREACH = 'response_breach';
    public const TYPE_RESOLUTION_WARNING = 'resolution_warning';
    public const TYPE_RESOLUTION_BREACH = 'resolution_breach';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'ticket_id',
        'type',
        'status',
        'triggered_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
