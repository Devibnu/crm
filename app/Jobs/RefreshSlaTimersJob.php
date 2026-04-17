<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\SlaTimerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshSlaTimersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SlaTimerService $slaTimerService): void
    {
        Ticket::query()
            ->with('slaDefinition')
            ->whereIn('status', ['open', 'in_progress'])
            ->chunkById(100, function ($tickets) use ($slaTimerService): void {
                foreach ($tickets as $ticket) {
                    $slaTimerService->syncTicket($ticket);
                }
            });
    }
}