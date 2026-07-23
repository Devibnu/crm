<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Services\Sla\TicketSlaService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TicketWorkflowService
{
    public function __construct(
        protected TicketSlaService $ticketSlaService,
    ) {}

    /**
     * @var array<string, array<int, string>>
     */
    protected array $allowedTransitions = [
        'open' => ['in_progress'],
        'in_progress' => ['waiting_customer'],
        'waiting_customer' => ['resolved'],
        'resolved' => ['closed'],
        'closed' => ['reopened'],
        'reopened' => ['in_progress'],
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        $targetStatus = (string) ($data['status'] ?? $ticket->status);
        $priorityChanged = array_key_exists('priority', $data) && $data['priority'] !== $ticket->priority;

        if ($targetStatus !== $ticket->status) {
            $this->assertCanTransition($ticket, $targetStatus);
        }

        $ticket->update($this->withConsistentTimeline($ticket, $data, $targetStatus));
        $ticket->refresh();

        if ($priorityChanged) {
            $this->ticketSlaService->refreshForPriorityChange($ticket);
        }

        if (in_array($targetStatus, ['resolved', 'closed'], true)) {
            $this->ticketSlaService->evaluateResolution($ticket->refresh());
        }

        return $ticket->refresh();
    }

    public function assign(Ticket $ticket, ?string $assignedTo): Ticket
    {
        $ticket->update(['assigned_to' => $assignedTo]);

        return $ticket->refresh();
    }

    public function startProgress(Ticket $ticket): Ticket
    {
        return $this->transition($ticket, 'in_progress');
    }

    public function waitingCustomer(Ticket $ticket): Ticket
    {
        return $this->transition($ticket, 'waiting_customer');
    }

    public function resolve(Ticket $ticket): Ticket
    {
        return $this->transition($ticket, 'resolved');
    }

    public function close(Ticket $ticket): Ticket
    {
        return $this->transition($ticket, 'closed');
    }

    public function reopen(Ticket $ticket): Ticket
    {
        return $this->transition($ticket, 'reopened');
    }

    public function transition(Ticket $ticket, string $status): Ticket
    {
        return $this->update($ticket, ['status' => $status]);
    }

    public function canTransition(Ticket $ticket, string $status): bool
    {
        if ($status === $ticket->status) {
            return true;
        }

        return in_array($status, $this->allowedTransitions[$ticket->status] ?? [], true);
    }

    protected function assertCanTransition(Ticket $ticket, string $status): void
    {
        if ($this->canTransition($ticket, $status)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "Ticket status cannot transition from {$ticket->status} to {$status}.",
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withConsistentTimeline(Ticket $ticket, array $data, string $status): array
    {
        $data = Arr::except($data, ['resolved_at', 'closed_at']);

        if ($status === 'resolved') {
            $data['resolved_at'] = $ticket->resolved_at ?: now();
        }

        if ($status === 'closed') {
            $data['resolved_at'] = $ticket->resolved_at ?: now();
            $data['closed_at'] = $ticket->closed_at ?: now();
        }

        if ($status === 'reopened') {
            $data['resolved_at'] = $ticket->resolved_at;
            $data['closed_at'] = $ticket->closed_at;
        }

        return $data;
    }
}
