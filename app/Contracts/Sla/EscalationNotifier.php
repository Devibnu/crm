<?php

namespace App\Contracts\Sla;

use App\Models\Ticket;
use App\Models\TicketSlaEscalation;

interface EscalationNotifier
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function notify(Ticket $ticket, string $type, array $metadata = []): TicketSlaEscalation;
}
