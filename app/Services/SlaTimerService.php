<?php

namespace App\Services;

use App\Models\SLA;
use App\Models\Ticket;
use App\Models\TicketActivity;
use Carbon\CarbonImmutable;

class SlaTimerService
{
    public function syncTicket(Ticket $ticket): Ticket
    {
        $ticket->loadMissing('slaDefinition');

        $alertState = $this->resolveAlertState($ticket);
        $updates = [
            'alert_state' => $alertState,
        ];

        if ($ticket->status === 'resolved' && ! $ticket->resolved_at) {
            $updates['resolved_at'] = now();
        }

        if ($alertState !== 'on_track' && $alertState !== 'resolved' && $ticket->alert_state !== $alertState) {
            $updates['alert_sent_at'] = now();

            TicketActivity::record(
                $ticket,
                'sla_alert_placeholder',
                null,
                'SLA alert placeholder triggered',
                sprintf('Frontend placeholder notification prepared for state %s.', $alertState),
                [
                    'alertState' => $alertState,
                ],
            );
        }

        if ($ticket->slaDefinition?->auto_escalate && $alertState === 'overdue') {
            $escalationPriority = $ticket->slaDefinition->escalation_priority;

            if ($escalationPriority && $ticket->priority !== $escalationPriority) {
                $updates['priority'] = $escalationPriority;
                $updates['escalation_level'] = $ticket->escalation_level + 1;

                TicketActivity::record(
                    $ticket,
                    'auto_escalation_placeholder',
                    null,
                    'Automatic escalation placeholder triggered',
                    'Escalation placeholder executed. External escalation workflow can be connected in the next sprint.',
                    [
                        'fromPriority' => $ticket->priority,
                        'toPriority' => $escalationPriority,
                    ],
                );
            }
        }

        if ($updates !== ['alert_state' => $alertState] || $ticket->alert_state !== $alertState) {
            $ticket->forceFill($updates)->save();
        }

        return $ticket->fresh(['customer', 'assignedUser', 'slaDefinition', 'activities.user']);
    }

    public function applyDefinition(Ticket $ticket, ?SLA $definition = null): Ticket
    {
        $definition ??= $this->resolveDefinition($ticket->category, $ticket->priority);

        $ticket->forceFill([
            'sla_definition_id' => $definition?->id,
            'first_response_due_at' => $definition ? CarbonImmutable::parse($ticket->created_at)->addMinutes($definition->first_response_minutes) : null,
            'resolution_due_at' => $definition ? CarbonImmutable::parse($ticket->created_at)->addMinutes($definition->resolution_minutes) : null,
            'alert_state' => 'on_track',
        ])->save();

        return $ticket->fresh(['slaDefinition']);
    }

    public function resolveDefinition(string $category, string $priority): ?SLA
    {
        return SLA::query()
            ->where('is_active', true)
            ->where('priority', $priority)
            ->where(fn ($query) => $query
                ->where('category', $category)
                ->orWhereNull('category'))
            ->orderByRaw('category is null')
            ->first();
    }

    public function resolveAlertState(Ticket $ticket): string
    {
        if ($ticket->status === 'resolved') {
            return 'resolved';
        }

        if (! $ticket->resolution_due_at) {
            return 'on_track';
        }

        $dueAt = CarbonImmutable::parse($ticket->resolution_due_at);
        $now = CarbonImmutable::now();
        $warningThreshold = $ticket->slaDefinition?->warning_before_minutes ?? 60;

        if ($dueAt->lessThanOrEqualTo($now)) {
            return 'overdue';
        }

        if ($dueAt->lessThanOrEqualTo($now->addMinutes($warningThreshold))) {
            return 'due_soon';
        }

        return 'on_track';
    }

    public function buildAlertCodes(Ticket $ticket): array
    {
        $state = $this->resolveAlertState($ticket);

        return match ($state) {
            'due_soon' => ['sla_due_soon'],
            'overdue' => array_values(array_filter([
                'sla_overdue',
                $ticket->slaDefinition?->auto_escalate ? 'escalation_placeholder' : null,
            ])),
            default => [],
        };
    }
}