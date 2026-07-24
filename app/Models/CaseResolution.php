<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseResolution extends Model
{
    use HasFactory;

    public const TYPE_WORKAROUND = 'workaround';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_DUPLICATE = 'duplicate';
    public const TYPE_INVALID = 'invalid';
    public const TYPE_ESCALATED = 'escalated';

    public const ROOT_CAUSE_SOFTWARE_BUG = 'software_bug';
    public const ROOT_CAUSE_CONFIGURATION = 'configuration';
    public const ROOT_CAUSE_NETWORK = 'network';
    public const ROOT_CAUSE_HARDWARE = 'hardware';
    public const ROOT_CAUSE_HUMAN_ERROR = 'human_error';
    public const ROOT_CAUSE_SECURITY = 'security';
    public const ROOT_CAUSE_THIRD_PARTY = 'third_party';
    public const ROOT_CAUSE_DUPLICATE = 'duplicate';
    public const ROOT_CAUSE_UNKNOWN = 'unknown';
    public const ROOT_CAUSE_OTHER = 'other';

    public const OUTCOME_RESOLVED = 'resolved';
    public const OUTCOME_WORKAROUND = 'workaround';
    public const OUTCOME_ESCALATED = 'escalated';
    public const OUTCOME_DUPLICATE = 'duplicate';
    public const OUTCOME_CANNOT_REPRODUCE = 'cannot_reproduce';
    public const OUTCOME_WONT_FIX = 'wont_fix';
    public const OUTCOME_CANCELLED = 'cancelled';

    protected $fillable = [
        'ticket_id',
        'resolution_summary',
        'resolution_notes',
        'root_cause',
        'workaround',
        'permanent_fix',
        'internal_notes',
        'resolution_type',
        'resolution_outcome',
        'reopened_count',
        'knowledge_candidate',
        'knowledge_article_id',
        'resolved_by',
        'resolved_at',
        'customer_notified',
        'customer_notified_at',
        'customer_confirmation_at',
        'resolution_duration_minutes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'customer_notified' => 'boolean',
        'customer_notified_at' => 'datetime',
        'customer_confirmation_at' => 'datetime',
        'knowledge_candidate' => 'boolean',
        'reopened_count' => 'integer',
        'resolution_duration_minutes' => 'integer',
    ];

    /**
     * @return array<int, string>
     */
    public static function resolutionTypeOptions(): array
    {
        return [
            self::TYPE_WORKAROUND,
            self::TYPE_FIXED,
            self::TYPE_DUPLICATE,
            self::TYPE_INVALID,
            self::TYPE_ESCALATED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function rootCauseOptions(): array
    {
        return [
            self::ROOT_CAUSE_SOFTWARE_BUG,
            self::ROOT_CAUSE_CONFIGURATION,
            self::ROOT_CAUSE_NETWORK,
            self::ROOT_CAUSE_HARDWARE,
            self::ROOT_CAUSE_HUMAN_ERROR,
            self::ROOT_CAUSE_SECURITY,
            self::ROOT_CAUSE_THIRD_PARTY,
            self::ROOT_CAUSE_DUPLICATE,
            self::ROOT_CAUSE_UNKNOWN,
            self::ROOT_CAUSE_OTHER,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function resolutionOutcomeOptions(): array
    {
        return [
            self::OUTCOME_RESOLVED,
            self::OUTCOME_WORKAROUND,
            self::OUTCOME_ESCALATED,
            self::OUTCOME_DUPLICATE,
            self::OUTCOME_CANNOT_REPRODUCE,
            self::OUTCOME_WONT_FIX,
            self::OUTCOME_CANCELLED,
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function knowledgeArticle(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class, 'knowledge_article_id');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($search) {
            $innerQuery
                ->where('resolution_summary', 'like', "%{$search}%")
                ->orWhere('resolution_notes', 'like', "%{$search}%")
                ->orWhere('root_cause', 'like', "%{$search}%")
                ->orWhere('resolution_outcome', 'like', "%{$search}%")
                ->orWhere('resolved_by', 'like', "%{$search}%")
                ->orWhereHas('ticket', function (Builder $ticketQuery) use ($search) {
                    $ticketQuery
                        ->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        });
                });
        });
    }

    public function scopeFilterResolutionType(Builder $query, string $type, array $allowed): Builder
    {
        if (! in_array($type, $allowed, true)) {
            return $query;
        }

        return $query->where('resolution_type', $type);
    }

    public function scopeFilterCustomerNotified(Builder $query, string $notified): Builder
    {
        if (! in_array($notified, ['yes', 'no'], true)) {
            return $query;
        }

        return $query->where('customer_notified', $notified === 'yes');
    }

    public function scopeFilterRootCause(Builder $query, string $rootCause): Builder
    {
        if (! in_array($rootCause, self::rootCauseOptions(), true)) {
            return $query;
        }

        return $query->where('root_cause', $rootCause);
    }

    public function scopeFilterOutcome(Builder $query, string $outcome): Builder
    {
        if (! in_array($outcome, self::resolutionOutcomeOptions(), true)) {
            return $query;
        }

        return $query->where('resolution_outcome', $outcome);
    }

    public function scopeFilterKnowledgeCandidate(Builder $query, string $candidate): Builder
    {
        if (! in_array($candidate, ['yes', 'no'], true)) {
            return $query;
        }

        return $query->where('knowledge_candidate', $candidate === 'yes');
    }

    public function scopeFilterDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $dateQuery) => $dateQuery->whereDate('resolved_at', '>=', $from))
            ->when($to, fn (Builder $dateQuery) => $dateQuery->whereDate('resolved_at', '<=', $to));
    }
}
