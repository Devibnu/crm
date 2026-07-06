<?php

use App\Jobs\SendWhatsAppBroadcastJob;
use App\Models\Lead;
use App\Models\OmnichannelMessage;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppBroadcastReply;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppProvider;
use App\Services\WhatsApp\WhatsAppConversationService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('whatsapp:backfill-customers {--conversation_id= : Backfill one WhatsApp conversation id}', function (WhatsAppConversationService $whatsAppService) {
    $summary = [
        'customers_created' => 0,
        'conversations_linked' => 0,
        'leads_linked' => 0,
        'opportunities_linked' => 0,
        'quotations_linked' => 0,
        'skipped' => 0,
    ];

    $conversationId = $this->option('conversation_id');
    $processed = 0;
    $query = WhatsAppConversation::query()
        ->when($conversationId, fn ($builder) => $builder->whereKey((int) $conversationId))
        ->orderBy('id');

    $query->chunkById(100, function ($conversations) use (&$summary, &$processed, $whatsAppService): void {
        foreach ($conversations as $conversation) {
            $processed++;
            $phone = $whatsAppService->normalizePhoneNumber((string) $conversation->phone_number);

            if ($phone === '') {
                $summary['skipped']++;
                continue;
            }

            $matchingLeads = Lead::query()
                ->get(['id', 'customer_id', 'name', 'phone', 'whatsapp'])
                ->filter(fn (Lead $lead): bool => collect([$lead->phone, $lead->whatsapp])
                    ->filter()
                    ->contains(fn (string $value): bool => $whatsAppService->normalizePhoneNumber($value) === $phone))
                ->values();

            $customerName = $conversation->contact_name
                ?: $matchingLeads->first()?->name
                ?: "WhatsApp Customer {$phone}";

            $existingCustomer = $whatsAppService->findCustomerByPhone($phone);
            $customer = $existingCustomer ?: $whatsAppService->findOrCreateCustomerFromInbound($phone, $customerName, 'conversation backfill');

            if ($existingCustomer === null) {
                $summary['customers_created']++;
            }

            $conversationUpdates = [];

            if ((int) $conversation->customer_id !== (int) $customer->id) {
                $conversationUpdates['customer_id'] = $customer->id;
            }

            if (blank($conversation->lead_id) && $matchingLeads->isNotEmpty()) {
                $conversationUpdates['lead_id'] = $matchingLeads->first()->id;
            }

            if ($conversationUpdates !== []) {
                $conversation->update($conversationUpdates);
                $summary['conversations_linked']++;
            }

            $leadLinkedForConversation = 0;

            foreach ($matchingLeads as $lead) {
                if ((int) $lead->customer_id === (int) $customer->id) {
                    continue;
                }

                $lead->update(['customer_id' => $customer->id]);
                $summary['leads_linked']++;
                $leadLinkedForConversation++;
            }

            $leadIds = $matchingLeads->pluck('id');
            $opportunitiesLinked = 0;
            $quotationsLinked = 0;

            if ($leadIds->isNotEmpty()) {
                $opportunitiesLinked = Opportunity::query()
                    ->whereIn('lead_id', $leadIds)
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $customer->id]);
                $summary['opportunities_linked'] += $opportunitiesLinked;

                $linkedOpportunityIds = Opportunity::query()
                    ->whereIn('lead_id', $leadIds)
                    ->where('customer_id', $customer->id)
                    ->pluck('id');

                if ($linkedOpportunityIds->isNotEmpty()) {
                    $quotationsLinked = Quotation::query()
                        ->whereIn('opportunity_id', $linkedOpportunityIds)
                        ->whereNull('customer_id')
                        ->update(['customer_id' => $customer->id]);
                    $summary['quotations_linked'] += $quotationsLinked;
                }
            }

            if ($conversationUpdates === [] && $leadLinkedForConversation === 0 && $opportunitiesLinked === 0 && $quotationsLinked === 0) {
                $summary['skipped']++;
            }
        }
    });

    if ($conversationId && $processed === 0) {
        $summary['skipped']++;
        $this->warn("WhatsApp conversation #{$conversationId} tidak ditemukan.");
    }

    Log::info('WhatsApp customer backfill finished.', $summary + [
        'conversation_id' => $conversationId ? (int) $conversationId : null,
    ]);

    $this->info('WhatsApp customer backfill finished.');
    $this->table(
        ['Metric', 'Total'],
        collect($summary)->map(fn (int $total, string $metric): array => [$metric, $total])->values()->all(),
    );

    return Command::SUCCESS;
})->purpose('Backfill customers and lead links for old WhatsApp conversations');

Artisan::command('whatsapp:broadcast-clean-testing {--force : Run without confirmation}', function () {
    if (! $this->option('force') && ! $this->confirm('Bersihkan seluruh data testing WhatsApp Broadcast?')) {
        $this->warn('Cleanup dibatalkan.');

        return Command::SUCCESS;
    }

    $deleted = DB::transaction(function () {
        $counts = [
            'replies' => WhatsAppBroadcastReply::query()->count(),
            'recipients' => WhatsAppBroadcastRecipient::query()->count(),
            'omnichannel' => OmnichannelMessage::query()
                ->where('channel', 'whatsapp')
                ->where(function ($query) {
                    $query
                        ->where('subject', 'like', '%broadcast%')
                        ->orWhere('message', 'like', '%broadcast%')
                        ->orWhere('sender_name', 'like', '%broadcast%')
                        ->orWhere('sender_name', 'like', '%Broadcast%');
                })
                ->count(),
            'jobs' => DB::table('jobs')->where('queue', 'whatsapp-broadcasts')->count(),
            'failed_jobs' => DB::table('failed_jobs')->where('queue', 'whatsapp-broadcasts')->count(),
            'broadcasts' => WhatsAppBroadcast::query()->count(),
        ];

        WhatsAppBroadcastReply::query()->delete();
        WhatsAppBroadcastRecipient::query()->delete();

        OmnichannelMessage::query()
            ->where('channel', 'whatsapp')
            ->where(function ($query) {
                $query
                    ->where('subject', 'like', '%broadcast%')
                    ->orWhere('message', 'like', '%broadcast%')
                    ->orWhere('sender_name', 'like', '%broadcast%')
                    ->orWhere('sender_name', 'like', '%Broadcast%');
            })
            ->delete();

        DB::table('jobs')->where('queue', 'whatsapp-broadcasts')->delete();
        DB::table('failed_jobs')->where('queue', 'whatsapp-broadcasts')->delete();

        WhatsAppBroadcast::query()->update([
            'total_recipients' => 0,
            'sent_count' => 0,
            'total_sent' => 0,
            'delivered_count' => 0,
            'read_count' => 0,
            'replied_count' => 0,
            'failed_count' => 0,
            'total_failed' => 0,
            'delivery_rate' => 0,
            'reply_rate' => 0,
        ]);
        WhatsAppBroadcast::query()->delete();

        return $counts;
    });

    $this->info('Data testing WhatsApp Broadcast berhasil dibersihkan.');
    $this->table(['Data', 'Jumlah Dihapus'], collect($deleted)->map(fn ($count, $label) => [$label, $count])->values()->all());

    return Command::SUCCESS;
})->purpose('Clean old testing data from WhatsApp Broadcast without touching customers, providers, campaigns, or system settings');

Artisan::command('whatsapp:broadcast-debug {broadcast_id}', function () {
    $broadcastId = (int) $this->argument('broadcast_id');
    $broadcast = WhatsAppBroadcast::query()->find($broadcastId);

    if ($broadcast === null) {
        $this->error("WhatsApp broadcast #{$broadcastId} tidak ditemukan.");

        return Command::FAILURE;
    }

    $statusCounts = WhatsAppBroadcastRecipient::query()
        ->where('whatsapp_broadcast_id', $broadcast->id)
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

    $broadcastJobPayloads = DB::table('jobs')
        ->where('payload', 'like', '%SendWhatsAppBroadcastJob%')
        ->pluck('payload');
    $pendingBroadcastJobs = $broadcastJobPayloads
        ->filter(function (string $payload) use ($broadcast) {
            $decoded = json_decode($payload, true);
            $command = (string) data_get($decoded, 'data.command', '');

            return str_contains($command, 's:11:"broadcastId";i:'.$broadcast->id.';');
        })
        ->count();
    $totalBroadcastJobs = $broadcastJobPayloads->count();

    $provider = WhatsAppProvider::query()->default()->active()->first();
    $sampleRecipient = WhatsAppBroadcastRecipient::query()
        ->where('whatsapp_broadcast_id', $broadcast->id)
        ->orderBy('id')
        ->first();

    $normalizePhone = function (?string $phone): ?string {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return str_starts_with($digits, '62') && strlen($digits) >= 10 ? $digits : null;
    };

    $this->info("WhatsApp Broadcast #{$broadcast->id}: {$broadcast->name}");
    $this->table(['Metric', 'Value'], [
        ['Broadcast status', $broadcast->status],
        ['Total recipients', $broadcast->recipients()->count()],
        ['Recipient status count', $statusCounts->map(fn ($total, $status) => "{$status}: {$total}")->values()->implode(', ') ?: '-'],
        ['Queued', (int) ($statusCounts['queued'] ?? 0)],
        ['Sending', (int) ($statusCounts['sending'] ?? 0)],
        ['Sent', (int) ($statusCounts['sent'] ?? 0)],
        ['Failed', (int) ($statusCounts['failed'] ?? 0)],
        ['Jobs count for broadcast', $pendingBroadcastJobs],
        ['Jobs count total broadcast', $totalBroadcastJobs],
        ['Queue connection', config('queue.default')],
        ['Default queue', config('queue.default')],
        ['Database queue name', config('queue.connections.database.queue')],
        ['Default provider', $provider ? "{$provider->name} ({$provider->provider})" : 'none'],
        ['Default provider status', $provider?->status ?? 'none'],
        ['Sample recipient id', $sampleRecipient?->id ?? '-'],
        ['Sample recipient status', $sampleRecipient?->status ?? '-'],
        ['Sample recipient phone', $sampleRecipient?->phone_number ?? '-'],
        ['Sample phone normalized', $normalizePhone($sampleRecipient?->phone_number) ?? 'invalid'],
    ]);

    return Command::SUCCESS;
})->purpose('Debug WhatsApp Broadcast queue status and provider readiness');

Artisan::command('whatsapp:broadcast-dispatch {broadcast_id}', function () {
    $broadcastId = (int) $this->argument('broadcast_id');
    $broadcast = WhatsAppBroadcast::query()->find($broadcastId);

    if ($broadcast === null) {
        $this->error("WhatsApp broadcast #{$broadcastId} tidak ditemukan.");

        return Command::FAILURE;
    }

    if (! in_array($broadcast->status, ['sending', 'scheduled'], true)) {
        $broadcast->update(['status' => 'sending']);
    }

    $queuedRecipients = $broadcast->recipients()
        ->where('status', 'queued')
        ->orderBy('id')
        ->get(['id', 'whatsapp_broadcast_id']);
    $dispatched = 0;

    foreach ($queuedRecipients as $recipient) {
        Log::info('Dispatching broadcast recipient job', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
        ]);

        SendWhatsAppBroadcastJob::dispatch($broadcast->id, $recipient->id);
        $dispatched++;
    }

    Log::info('Jobs queued', [
        'broadcast_id' => $broadcast->id,
        'dispatched' => $dispatched,
        'count' => DB::table('jobs')->count(),
    ]);

    $this->line("Broadcast {$broadcast->id}");
    $this->line("Queued recipients: {$queuedRecipients->count()}");
    $this->line("Dispatched jobs: {$dispatched}");

    return Command::SUCCESS;
})->purpose('Dispatch queued WhatsApp Broadcast recipients back into the queue');
