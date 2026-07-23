<?php

namespace App\Services\Sla;

use App\Contracts\Sla\EscalationNotifier;
use App\Models\BusinessCalendar;
use App\Models\Ticket;
use App\Models\TicketSlaEscalation;
use App\Services\BusinessCalendar\BusinessTimeCalculator;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SlaEscalationService
{
    public function __construct(
        protected BusinessTimeCalculator $businessTimeCalculator,
        protected EscalationNotifier $notifier,
    ) {}

    /**
     * @return array{checked: int, warnings: int, breaches: int, skipped: int}
     */
    public function evaluateAllOpenTickets(): array
    {
        $summary = [
            'checked' => 0,
            'warnings' => 0,
            'breaches' => 0,
            'skipped' => 0,
        ];

        Ticket::query()
            ->with(['slaPolicy', 'slaBusinessCalendar.workingHours', 'slaBusinessCalendar.holidays'])
            ->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'reopened'])
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use (&$summary): void {
                foreach ($tickets as $ticket) {
                    $result = $this->evaluateTicket($ticket);

                    foreach ($summary as $key => $value) {
                        $summary[$key] += $result[$key];
                    }
                }
            });

        return $summary;
    }

    /**
     * @return array{checked: int, warnings: int, breaches: int, skipped: int}
     */
    public function evaluateTicket(Ticket $ticket): array
    {
        $ticket->loadMissing(['slaPolicy', 'slaBusinessCalendar.workingHours', 'slaBusinessCalendar.holidays']);

        $summary = [
            'checked' => 1,
            'warnings' => 0,
            'breaches' => 0,
            'skipped' => 0,
        ];

        if ($ticket->status === 'closed' || ! $ticket->sla_policy_id) {
            $summary['skipped'] = 1;

            return $summary;
        }

        $summary['warnings'] += $this->evaluateResponseWarning($ticket) ? 1 : 0;
        $summary['breaches'] += $this->evaluateResponseBreach($ticket) ? 1 : 0;

        if ($ticket->status !== 'resolved') {
            $summary['warnings'] += $this->evaluateResolutionWarning($ticket) ? 1 : 0;
            $summary['breaches'] += $this->evaluateResolutionBreach($ticket) ? 1 : 0;
        }

        return $summary;
    }

    protected function evaluateResponseWarning(Ticket $ticket): bool
    {
        if ($ticket->first_responded_at || ! $ticket->sla_response_time_minutes) {
            return false;
        }

        return $this->fireIfDue(
            $ticket,
            TicketSlaEscalation::TYPE_RESPONSE_WARNING,
            $this->warningAt($ticket, $ticket->sla_response_time_minutes, (int) ($ticket->slaPolicy?->response_warning_percentage ?? 80)),
            greaterThanOnly: false,
        );
    }

    protected function evaluateResponseBreach(Ticket $ticket): bool
    {
        if ($ticket->first_responded_at || ! $ticket->response_due_at) {
            return false;
        }

        return $this->fireIfDue($ticket, TicketSlaEscalation::TYPE_RESPONSE_BREACH, $ticket->response_due_at);
    }

    protected function evaluateResolutionWarning(Ticket $ticket): bool
    {
        if ($ticket->resolved_at || ! $ticket->sla_resolution_time_minutes) {
            return false;
        }

        return $this->fireIfDue(
            $ticket,
            TicketSlaEscalation::TYPE_RESOLUTION_WARNING,
            $this->warningAt($ticket, $ticket->sla_resolution_time_minutes, (int) ($ticket->slaPolicy?->resolution_warning_percentage ?? 80)),
            greaterThanOnly: false,
        );
    }

    protected function evaluateResolutionBreach(Ticket $ticket): bool
    {
        if ($ticket->resolved_at || ! $ticket->resolution_due_at) {
            return false;
        }

        return $this->fireIfDue($ticket, TicketSlaEscalation::TYPE_RESOLUTION_BREACH, $ticket->resolution_due_at);
    }

    protected function fireIfDue(Ticket $ticket, string $type, ?CarbonInterface $dueAt, bool $greaterThanOnly = true): bool
    {
        if (! $dueAt || $this->hasEscalation($ticket, $type)) {
            return false;
        }

        $now = $this->comparableNow($ticket, $dueAt);
        $shouldFire = $greaterThanOnly
            ? $now->greaterThan($dueAt)
            : $now->greaterThanOrEqualTo($dueAt);

        if (! $shouldFire) {
            return false;
        }

        $escalation = $this->notifier->notify($ticket, $type, [
            'ticket_number' => $ticket->ticket_number,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'due_at' => $dueAt->toDateTimeString(),
        ]);

        return $escalation->wasRecentlyCreated;
    }

    protected function warningAt(Ticket $ticket, int $targetMinutes, int $percentage): ?CarbonInterface
    {
        if (! $ticket->created_at) {
            return null;
        }

        $warningMinutes = max(1, (int) floor($targetMinutes * $percentage / 100));
        $calendar = $this->resolvedCalendar($ticket);

        if (! $calendar) {
            return Carbon::parse($ticket->created_at)->addMinutes($warningMinutes);
        }

        return $this->businessTimeCalculator->addBusinessMinutes($ticket->created_at, $warningMinutes, $calendar);
    }

    protected function resolvedCalendar(Ticket $ticket): ?BusinessCalendar
    {
        $calendar = $ticket->relationLoaded('slaBusinessCalendar')
            ? $ticket->slaBusinessCalendar
            : $ticket->slaBusinessCalendar()->first();

        if ($calendar?->is_active) {
            return $calendar->loadMissing(['workingHours', 'holidays']);
        }

        return null;
    }

    protected function comparableNow(Ticket $ticket, CarbonInterface $dueAt): CarbonInterface
    {
        $calendar = $this->resolvedCalendar($ticket);

        if (! $calendar) {
            return now();
        }

        return Carbon::parse(now($calendar->timezone)->format('Y-m-d H:i:s'), $dueAt->timezone);
    }

    protected function hasEscalation(Ticket $ticket, string $type): bool
    {
        if ($ticket->relationLoaded('slaEscalations')) {
            return $ticket->slaEscalations->contains('type', $type);
        }

        return $ticket->slaEscalations()->where('type', $type)->exists();
    }
}
