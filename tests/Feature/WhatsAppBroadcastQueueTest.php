<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppBroadcastJob;
use App\Models\Customer;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppBroadcastQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_broadcast_dispatches_job_per_queued_recipient(): void
    {
        Queue::fake();

        $broadcast = WhatsAppBroadcast::factory()->create(['status' => 'draft']);
        WhatsAppBroadcastRecipient::factory()->count(3)->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'queued',
        ]);

        $this->post(route('admin.marketing.whatsapp-broadcasts.start', $broadcast))
            ->assertRedirect(route('admin.marketing.whatsapp-broadcasts.show', $broadcast));

        $this->assertSame('sending', $broadcast->fresh()->status);
        Queue::assertPushed(SendWhatsAppBroadcastJob::class, 3);
        Queue::assertPushed(SendWhatsAppBroadcastJob::class, fn (SendWhatsAppBroadcastJob $job) => $job->queue === null);
    }

    public function test_create_broadcast_with_sending_status_dispatches_jobs(): void
    {
        Queue::fake();

        Customer::factory()->create(['phone' => '6281111111111']);
        Customer::factory()->create(['phone' => '6282222222222']);

        $this->post(route('admin.marketing.whatsapp-broadcasts.store'), $this->broadcastPayload([
            'status' => 'sending',
            'target_type' => 'customer',
            'recipient_type' => 'customer',
        ]))->assertRedirect();

        Queue::assertPushed(SendWhatsAppBroadcastJob::class, 2);
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 2);
        $this->assertDatabaseHas('whatsapp_broadcasts', [
            'status' => 'sending',
            'total_recipients' => 2,
        ]);
    }

    public function test_job_updates_recipient_status_after_successful_send(): void
    {
        $this->fakeSuccessfulProvider('msg-123');

        $broadcast = WhatsAppBroadcast::factory()->create([
            'message_template' => 'Halo {{name}}',
            'status' => 'sending',
            'total_recipients' => 1,
        ]);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_name' => 'Budi',
            'phone_number' => '6281234567890',
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        $recipient->refresh();

        $this->assertSame('sent', $recipient->status);
        $this->assertNotSame('queued', $recipient->status);
        $this->assertSame('msg-123', $recipient->provider_message_id);
        $this->assertNotNull($recipient->sent_at);
        $this->assertNull($recipient->error_message);

        Http::assertSent(fn ($request) => $request['message'] === 'Halo Budi');
    }

    public function test_message_template_replaces_nama_placeholder(): void
    {
        $this->fakeSuccessfulProvider('msg-nama');

        $broadcast = WhatsAppBroadcast::factory()->create([
            'message_template' => 'Halo {{nama}}',
            'status' => 'sending',
            'total_recipients' => 1,
        ]);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_name' => 'Nama Customer',
            'phone_number' => '6281234567890',
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        Http::assertSent(fn ($request) => $request['message'] === 'Halo Nama Customer');
        $this->assertSame('sent', $recipient->fresh()->status);
    }

    public function test_message_template_still_replaces_name_placeholder(): void
    {
        $this->fakeSuccessfulProvider('msg-name');

        $broadcast = WhatsAppBroadcast::factory()->create([
            'message_template' => 'Halo {{name}}',
            'status' => 'sending',
            'total_recipients' => 1,
        ]);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_name' => 'Nama Customer',
            'phone_number' => '6281234567890',
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        Http::assertSent(fn ($request) => $request['message'] === 'Halo Nama Customer');
        $this->assertSame('sent', $recipient->fresh()->status);
    }

    public function test_database_queue_worker_processes_one_recipient(): void
    {
        config(['queue.default' => 'database']);
        $this->fakeSuccessfulProvider('msg-worker');

        $broadcast = WhatsAppBroadcast::factory()->create([
            'message_template' => 'Halo {{name}}',
            'status' => 'sending',
            'total_recipients' => 1,
        ]);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_name' => 'Worker Customer',
            'phone_number' => '081234567890',
            'status' => 'queued',
        ]);

        SendWhatsAppBroadcastJob::dispatch($broadcast->id, $recipient->id);

        $this->assertDatabaseHas('jobs', ['queue' => 'default']);

        Artisan::call('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
        ]);

        $recipient->refresh();

        $this->assertSame('sent', $recipient->status);
        $this->assertSame('6281234567890', $recipient->phone_number);
        $this->assertSame('msg-worker', $recipient->provider_message_id);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_failed_job_marks_recipient_failed_after_retries_are_exhausted(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create(['status' => 'sending']);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'sending',
        ]);
        $job = new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 90], $job->backoff());

        $job->failed(new \RuntimeException('Provider timeout'));

        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'id' => $recipient->id,
            'status' => 'failed',
            'error_message' => 'Provider timeout',
        ]);
    }

    public function test_failed_provider_response_updates_recipient_failed(): void
    {
        $this->fakeFailedProvider('Invalid target');

        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'total_recipients' => 1,
        ]);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'phone_number' => '6281234567890',
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'id' => $recipient->id,
            'status' => 'failed',
            'error_message' => 'Invalid target',
        ]);
        $this->assertNotSame('queued', $recipient->fresh()->status);
        $this->assertSame(1, $broadcast->fresh()->failed_count);
    }

    public function test_recipient_phone_is_normalized_before_send(): void
    {
        $this->fakeSuccessfulProvider('msg-normalized');

        $broadcast = WhatsAppBroadcast::factory()->create([
            'message_template' => 'Halo {phone}',
            'status' => 'sending',
            'total_recipients' => 2,
        ]);
        $plusRecipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'phone_number' => '+62 812-3456-7890',
            'status' => 'queued',
        ]);
        $zeroRecipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'phone_number' => '0812 9999 0000',
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $plusRecipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));
        (new SendWhatsAppBroadcastJob($broadcast->id, $zeroRecipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        $this->assertSame('6281234567890', $plusRecipient->fresh()->phone_number);
        $this->assertSame('6281299990000', $zeroRecipient->fresh()->phone_number);

        Http::assertSent(fn ($request) => $request['target'] === '6281234567890');
        Http::assertSent(fn ($request) => $request['target'] === '6281299990000');
    }

    public function test_paused_broadcast_is_logged_and_skipped(): void
    {
        Log::spy();

        $broadcast = WhatsAppBroadcast::factory()->create(['status' => 'paused']);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        $this->assertSame('queued', $recipient->fresh()->status);
        Log::shouldHaveReceived('info')
            ->with('Skipping WhatsApp broadcast recipient', \Mockery::on(fn (array $context) => $context['reason'] === 'broadcast_paused'))
            ->once();
    }

    public function test_job_updates_broadcast_aggregate_counts_and_rates(): void
    {
        $this->fakeSuccessfulProvider('msg-aggregate');

        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'total_recipients' => 2,
            'sent_count' => 0,
            'failed_count' => 0,
            'replied_count' => 1,
        ]);
        $sentRecipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'sent',
        ]);
        $queuedRecipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'phone_number' => '6281234567890',
            'status' => 'queued',
        ]);
        WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'failed',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $queuedRecipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        $broadcast->refresh();

        $this->assertSame(3, $broadcast->total_recipients);
        $this->assertSame(2, $broadcast->sent_count);
        $this->assertSame(2, $broadcast->total_sent);
        $this->assertSame(1, $broadcast->failed_count);
        $this->assertSame(1, $broadcast->total_failed);
        $this->assertSame('0.00', (string) $broadcast->delivery_rate);
        $this->assertSame('0.00', (string) $broadcast->reply_rate);
        $this->assertSame('sent', $sentRecipient->fresh()->status);
    }

    public function test_queue_processing_with_sync_connection_sends_broadcast(): void
    {
        config(['queue.default' => 'sync']);
        $this->fakeSuccessfulProvider('msg-sync');

        $customer = Customer::factory()->create([
            'name' => 'Sync Customer',
            'phone' => '628111222333',
        ]);

        $broadcast = WhatsAppBroadcast::factory()->create([
            'target_type' => 'customer',
            'status' => 'draft',
            'message_template' => 'Halo {name}',
        ]);
        WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_type' => 'customer',
            'recipient_id' => $customer->id,
            'recipient_name' => $customer->name,
            'phone_number' => $customer->phone,
            'status' => 'queued',
        ]);

        $this->post(route('admin.marketing.whatsapp-broadcasts.start', $broadcast))->assertRedirect();

        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'whatsapp_broadcast_id' => $broadcast->id,
            'phone_number' => '628111222333',
            'status' => 'sent',
            'provider_message_id' => 'msg-sync',
        ]);
        $this->assertSame('completed', $broadcast->fresh()->status);
    }

    public function test_pause_and_resume_broadcast_controls_queue_dispatch(): void
    {
        Queue::fake();

        $broadcast = WhatsAppBroadcast::factory()->create(['status' => 'sending']);
        WhatsAppBroadcastRecipient::factory()->count(2)->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'queued',
        ]);

        $this->post(route('admin.marketing.whatsapp-broadcasts.pause', $broadcast))->assertRedirect();
        $this->assertSame('paused', $broadcast->fresh()->status);

        $this->post(route('admin.marketing.whatsapp-broadcasts.resume', $broadcast))->assertRedirect();

        $this->assertSame('sending', $broadcast->fresh()->status);
        Queue::assertPushed(SendWhatsAppBroadcastJob::class, 2);
    }

    public function test_retry_queue_dispatches_queued_recipients(): void
    {
        Queue::fake();

        $broadcast = WhatsAppBroadcast::factory()->create(['status' => 'sending']);
        WhatsAppBroadcastRecipient::factory()->count(4)->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'queued',
        ]);

        $this->post(route('admin.marketing.whatsapp-broadcasts.retry-queue', $broadcast))
            ->assertRedirect(route('admin.marketing.whatsapp-broadcasts.show', $broadcast));

        Queue::assertPushed(SendWhatsAppBroadcastJob::class, 4);
    }

    public function test_dispatch_command_dispatches_queued_recipients(): void
    {
        Queue::fake();

        $broadcast = WhatsAppBroadcast::factory()->create(['status' => 'sending']);
        WhatsAppBroadcastRecipient::factory()->count(4)->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'queued',
        ]);

        $this->artisan('whatsapp:broadcast-dispatch', [
            'broadcast_id' => $broadcast->id,
        ])
            ->expectsOutput("Broadcast {$broadcast->id}")
            ->expectsOutput('Queued recipients: 4')
            ->expectsOutput('Dispatched jobs: 4')
            ->assertExitCode(0);

        Queue::assertPushed(SendWhatsAppBroadcastJob::class, 4);
    }

    protected function broadcastPayload(array $overrides = []): array
    {
        return array_merge([
            'marketing_campaign_id' => null,
            'name' => 'Queue Broadcast',
            'message_template' => 'Halo {{name}}',
            'target_type' => 'customer',
            'status' => 'draft',
            'scheduled_at' => null,
            'sent_at' => null,
            'created_by' => 'CRM Test',
            'notes' => null,
            'recipient_type' => 'customer',
        ], $overrides);
    }

    protected function fakeSuccessfulProvider(string $messageId): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.fonnte.test/send' => Http::response([
                'status' => true,
                'id' => $messageId,
            ], 200),
        ]);

        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://api.fonnte.test',
            'api_token' => 'dummy-token',
        ]);
    }

    protected function fakeFailedProvider(string $reason): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.fonnte.test/send' => Http::response([
                'status' => false,
                'reason' => $reason,
            ], 200),
        ]);

        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://api.fonnte.test',
            'api_token' => 'dummy-token',
        ]);
    }
}
