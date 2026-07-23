<?php

namespace App\Services\Sla;

use App\Models\BusinessCalendar;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Services\BusinessCalendar\BusinessTimeCalculator;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TicketSlaService
{
    public function __construct(
        protected BusinessTimeCalculator $businessTimeCalculator,
    ) {}

    public function apply(Ticket $ticket): Ticket
    {
        $policy = $this->activePolicyForPriority($ticket->priority);
        $baseAt = Carbon::parse($ticket->created_at ?: now());

        if (! $policy) {
            $ticket->forceFill([
                'sla_policy_id' => null,
                'sla_response_time_minutes' => null,
                'sla_resolution_time_minutes' => null,
                'response_due_at' => null,
                'resolution_due_at' => null,
                'sla_response_breached_at' => null,
                'sla_resolution_breached_at' => null,
            ])->save();

            return $ticket->refresh();
        }

        $responseDueAt = $this->calculateDueAt($baseAt, $policy->response_time_minutes);
        $resolutionDueAt = $this->calculateDueAt($baseAt, $policy->resolution_time_minutes);

        $data = [
            'sla_policy_id' => $policy->id,
            'sla_response_time_minutes' => $policy->response_time_minutes,
            'sla_resolution_time_minutes' => $policy->resolution_time_minutes,
            'response_due_at' => $responseDueAt,
            'resolution_due_at' => $resolutionDueAt,
            'sla_response_breached_at' => null,
            'sla_resolution_breached_at' => null,
        ];

        if ($ticket->first_responded_at && $ticket->first_responded_at->greaterThan($responseDueAt)) {
            $data['sla_response_breached_at'] = $ticket->first_responded_at;
        }

        if ($ticket->resolved_at && $ticket->resolved_at->greaterThan($resolutionDueAt)) {
            $data['sla_resolution_breached_at'] = $ticket->resolved_at;
        }

        $ticket->forceFill($data)->save();

        return $ticket->refresh();
    }

    public function refreshForPriorityChange(Ticket $ticket): Ticket
    {
        return $this->apply($ticket);
    }

    public function markFirstResponse(Ticket $ticket, ?Carbon $respondedAt = null): Ticket
    {
        if ($ticket->first_responded_at) {
            return $ticket->refresh();
        }

        $respondedAt ??= now();

        $data = [
            'first_responded_at' => $respondedAt,
        ];

        if ($ticket->response_due_at && $respondedAt->greaterThan($ticket->response_due_at)) {
            $data['sla_response_breached_at'] = $respondedAt;
        }

        $ticket->forceFill($data)->save();

        return $ticket->refresh();
    }

    public function evaluateResolution(Ticket $ticket, ?Carbon $resolvedAt = null): Ticket
    {
        if (! $ticket->resolution_due_at) {
            return $ticket->refresh();
        }

        $resolvedAt ??= $ticket->resolved_at ?: now();

        if ($resolvedAt->greaterThan($ticket->resolution_due_at) && ! $ticket->sla_resolution_breached_at) {
            $ticket->forceFill([
                'sla_resolution_breached_at' => $resolvedAt,
            ])->save();
        }

        return $ticket->refresh();
    }

    protected function activePolicyForPriority(string $priority): ?SlaPolicy
    {
        return SlaPolicy::query()
            ->where('priority', $priority)
            ->where('is_active', true)
            ->first();
    }

    protected function calculateDueAt(CarbonInterface $baseAt, int $minutes): CarbonInterface
    {
        $calendar = $this->defaultCalendar();

        if (! $calendar) {
            return Carbon::instance($baseAt)->copy()->addMinutes($minutes);
        }

        return $this->businessTimeCalculator->addBusinessMinutes($baseAt, $minutes, $calendar);
    }

    protected function defaultCalendar(): ?BusinessCalendar
    {
        return BusinessCalendar::query()
            ->defaultCalendar()
            ->with(['workingHours', 'holidays'])
            ->first();
    }
}
