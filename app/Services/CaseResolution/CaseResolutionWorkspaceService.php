<?php

namespace App\Services\CaseResolution;

use App\Models\Ticket;

class CaseResolutionWorkspaceService
{
    public function recordTicketReopened(Ticket $ticket): void
    {
        $ticket->caseResolutions()
            ->latest('resolved_at')
            ->latest('id')
            ->limit(1)
            ->increment('reopened_count');
    }
}
