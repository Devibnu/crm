<?php

use App\Jobs\SendWhatsAppBroadcastJob;
use App\Models\OmnichannelMessage;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppBroadcastReply;
use App\Models\WhatsAppProvider;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
