<?php

namespace App\Services\Tickets;

use App\Models\Ticket;

class TicketNumberGenerator
{
    public function generate(): string
    {
        do {
            $number = 'TCK-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Ticket::query()->where('ticket_number', $number)->exists());

        return $number;
    }
}
