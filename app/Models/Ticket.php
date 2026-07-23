<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    /**
     * @return array<int, string>
     */
    public static function statusOptions(): array
    {
        return ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed', 'reopened'];
    }

    /**
     * @return array<int, string>
     */
    public static function priorityOptions(): array
    {
        return ['low', 'medium', 'high', 'urgent'];
    }

    /**
     * @return array<int, string>
     */
    public static function channelOptions(): array
    {
        return ['email', 'phone', 'whatsapp', 'web', 'social', 'walk_in'];
    }

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'lead_id',
        'whatsapp_message_id',
        'whatsapp_broadcast_reply_id',
        'conversation_id',
        'source_type',
        'source_id',
        'sla_policy_id',
        'sla_response_time_minutes',
        'sla_resolution_time_minutes',
        'subject',
        'description',
        'priority',
        'status',
        'channel',
        'assigned_to',
        'due_at',
        'response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'sla_response_breached_at',
        'sla_resolution_breached_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'sla_response_breached_at' => 'datetime',
        'sla_resolution_breached_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class);
    }

    public function whatsappBroadcastReply(): BelongsTo
    {
        return $this->belongsTo(WhatsAppBroadcastReply::class);
    }

    public function sourceConversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    public function responseSlaStatus(): string
    {
        if (! $this->sla_policy_id || ! $this->response_due_at) {
            return 'no_policy';
        }

        if ($this->sla_response_breached_at) {
            return 'breached';
        }

        if ($this->first_responded_at) {
            return $this->first_responded_at->greaterThan($this->response_due_at) ? 'breached' : 'on_time';
        }

        return now()->greaterThan($this->response_due_at) ? 'breached' : 'not_started';
    }

    public function resolutionSlaStatus(): string
    {
        if ($this->sla_resolution_breached_at) {
            return 'breached';
        }

        if ($this->resolved_at) {
            return $this->resolution_due_at && $this->resolved_at->greaterThan($this->resolution_due_at)
                ? 'breached'
                : 'completed';
        }

        if ($this->resolution_due_at && now()->greaterThan($this->resolution_due_at)) {
            return 'breached';
        }

        return 'in_progress';
    }

    public function overallSlaStatus(): string
    {
        if ($this->responseSlaStatus() === 'breached' || $this->resolutionSlaStatus() === 'breached') {
            return 'breached';
        }

        if (in_array($this->status, ['resolved', 'closed'], true)) {
            return 'completed';
        }

        $nextDueAt = collect([$this->response_due_at, $this->resolution_due_at])
            ->filter()
            ->sort()
            ->first();

        if ($nextDueAt && now()->lessThanOrEqualTo($nextDueAt) && now()->addHour()->greaterThanOrEqualTo($nextDueAt)) {
            return 'warning';
        }

        return 'on_track';
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('ticket_number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('assigned_to', 'like', "%{$search}%")
                ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                    $customerQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    public function scopeFilter(Builder $query, string $column, string $value, array $allowed): Builder
    {
        if (! in_array($value, $allowed, true)) {
            return $query;
        }

        return $query->where($column, $value);
    }
}
