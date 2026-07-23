<?php

namespace App\Services\Sla;

use App\Contracts\Sla\EscalationNotifier;
use App\Models\Ticket;
use App\Models\TicketSlaEscalation;

class DatabaseEscalationNotifier implements EscalationNotifier
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function notify(Ticket $ticket, string $type, array $metadata = []): TicketSlaEscalation
    {
        return TicketSlaEscalation::query()->firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'type' => $type,
            ],
            [
                'status' => TicketSlaEscalation::STATUS_PENDING,
                'triggered_at' => now(),
                'metadata' => $metadata,
            ],
        );
    }
}
