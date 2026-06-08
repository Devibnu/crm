<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppBroadcastJob;
use App\Models\Customer;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppMessageTemplate;
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

    public function test_queued_meta_template_recipient_is_processed_and_sent(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.meta-send'],
                ],
            ], 200),
        ]);

        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'meta-token',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
            'meta_template_name' => 'promo',
            'meta_template_language' => 'id',
        ]);

        $template = WhatsAppMessageTemplate::create([
            'provider_id' => WhatsAppProvider::query()->first()->id,
            'template_id' => 'template-1',
            'name' => 'promo',
            'safe_name' => 'promo',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Halo {{nama}}, promo tersedia.',
            'body_meta' => 'Halo {{1}}, promo tersedia.',
            'variable_mapping' => ['1' => 'nama'],
            'source' => 'manual',
            'last_synced_at' => now(),
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Meta Customer',
            'phone' => '6281234500000',
        ]);

        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'send_mode' => 'meta_template',
            'whatsapp_message_template_id' => $template->id,
            'message_template' => $template->body,
            'total_recipients' => 1,
        ]);

        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_type' => 'customer',
            'recipient_id' => $customer->id,
            'recipient_name' => $customer->name,
            'phone_number' => $customer->phone,
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        $this->assertSame('sent', $recipient->refresh()->status);
        $this->assertSame('completed', $broadcast->fresh()->status);
        Http::assertSent(fn ($request) => $request['template']['name'] === 'promo'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'Meta Customer'
        );
    }

    public function test_meta_template_infers_missing_mapping_and_sends_correct_parameter_count(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.meta-infer'],
                ],
            ], 200),
        ]);

        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'meta-token',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
            'meta_template_name' => 'promo_blank',
            'meta_template_language' => 'id',
        ]);

        $provider = WhatsAppProvider::query()->first();
        $template = WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'template_id' => 'template-2',
            'name' => 'promo_blank',
            'safe_name' => 'promo_blank',
            'category' => 'UTILITY',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Halo {{1}}, kode {{2}} sudah siap.',
            'body_meta' => 'Halo {{1}}, kode {{2}} sudah siap.',
            'source' => 'manual',
            'last_synced_at' => now(),
        ]);

        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'send_mode' => 'meta_template',
            'whatsapp_message_template_id' => $template->id,
            'message_template' => $template->body,
            'total_recipients' => 1,
        ]);

        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_name' => 'Fallback Recipient',
            'phone_number' => '6281234500001',
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))->handle(app(\App\Services\WhatsApp\WhatsAppManager::class));

        $this->assertSame('sent', $recipient->refresh()->status);
        Http::assertSent(fn ($request) => $request['template']['name'] === 'promo_blank'
            && count($request['template']['components'][0]['parameters']) === 2
        );
    }

    public function test_error_message_from_result_parses_raw_meta_error_array(): void
    {
        $job = new SendWhatsAppBroadcastJob(1, 1);
        $method = new \ReflectionMethod($job, 'errorMessageFromResult');
        $method->setAccessible(true);

        $result = [
            'success' => false,
            'raw' => [
                'error' => [
                    'message' => 'Template not approved',
                ],
            ],
        ];

        $this->assertSame('Template not approved', $method->invoke($job, $result));
    }

    public function test_job_does_not_crash_when_provider_returns_raw_error_array_and_broadcast_finishes(): void
    {
        $this->fakeFailedProviderRawMetaError();

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
            'error_message' => 'Template not approved',
        ]);
        $this->assertSame('failed', $broadcast->fresh()->status);
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

    public function test_complete_broadcast_flow_queued_to_sent(): void
    {
        Http::fake(['*' => Http::response(['status' => 'success', 'id' => 'msg_123'])]);

        WhatsAppProvider::factory()->create(['provider' => 'fonnte', 'is_default' => true, 'status' => 'active']);

        $customer = Customer::factory()->create(['phone' => '6281111111111', 'name' => 'John Doe']);

        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'draft',
            'target_type' => 'customer',
            'message_template' => 'Halo {{nama}}, terima kasih telah menghubungi kami.',
            'total_recipients' => 0,
        ]);

        $recipient = WhatsAppBroadcastRecipient::create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_id' => $customer->id,
            'recipient_type' => 'customer',
            'phone_number' => '6281111111111',
            'recipient_name' => $customer->name,
            'status' => 'queued',
        ]);

        // Broadcast starts
        $broadcast->update(['status' => 'sending']);
        $this->assertTrue($broadcast->recipients()->where('status', 'queued')->exists());

        // Verify recipient is still queued
        $recipient->refresh();
        $this->assertSame('queued', $recipient->status);

        // Process the job
        $job = new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id);
        $manager = $this->app->make(\App\Services\WhatsApp\WhatsAppManager::class);
        $job->handle($manager);

        // Verify final status
        $recipient->refresh();
        $broadcast->refresh();

        $this->assertSame('sent', $recipient->status, "Recipient should be sent, but got error: {$recipient->error_message}");
        $this->assertSame('msg_123', $recipient->provider_message_id);
        $this->assertNotNull($recipient->sent_at);
        $this->assertNull($recipient->error_message);
    }

    public function test_complete_flow_six_recipients_all_reach_sent(): void
    {
        $counter = 0;
        Http::fake(function () use (&$counter) {
            $counter++;
            return Http::response(['status' => 'success', 'id' => 'msg_'.$counter]);
        });

        WhatsAppProvider::factory()->create(['provider' => 'fonnte', 'is_default' => true, 'status' => 'active']);

        // Create 6 customers
        $customers = Customer::factory()->count(6)->create();
        $customers->each(fn ($c, $i) => $c->update(['phone' => '628'.str_pad($i + 1, 8, '0', STR_PAD_LEFT)]));

        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'draft',
            'target_type' => 'customer',
            'message_template' => 'Hello {{nama}}',
            'total_recipients' => 0,
        ]);

        // Create 6 queued recipients
        $recipients = [];
        $customers->each(function ($customer) use ($broadcast, &$recipients) {
            $recipients[] = WhatsAppBroadcastRecipient::create([
                'whatsapp_broadcast_id' => $broadcast->id,
                'recipient_id' => $customer->id,
                'recipient_type' => 'customer',
                'phone_number' => $customer->phone,
                'recipient_name' => $customer->name,
                'status' => 'queued',
            ]);
        });

        $this->assertSame(6, $broadcast->recipients()->where('status', 'queued')->count());

        // Start broadcast - simulate what start() controller method does
        $broadcast->update(['status' => 'sending']);

        // Dispatch and process all recipients
        $manager = $this->app->make(\App\Services\WhatsApp\WhatsAppManager::class);
        $broadcast->recipients()
            ->where('status', 'queued')
            ->get()
            ->each(function (WhatsAppBroadcastRecipient $recipient) use ($broadcast, $manager) {
                $job = new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id);
                $job->handle($manager);
            });

        // Verify all reached 'sent' status
        $broadcast->refresh();
        $statuses = $broadcast->recipients()->pluck('status', 'id')->toArray();
        $this->assertTrue(collect($statuses)->every(fn ($status) => $status === 'sent'));
        $this->assertSame(0, $broadcast->recipients()->where('status', 'queued')->count());
        $this->assertSame(6, $broadcast->recipients()->where('status', 'sent')->count());
    }

    public function test_meta_provider_api_token_is_decrypted_for_broadcast(): void
    {
        // Create Meta provider with encrypted token
        $metaToken = 'EAA1234567890ABCDEF_meta_token_xyz123';
        $provider = WhatsAppProvider::create([
            'name' => 'Meta Test Provider',
            'provider' => 'meta',
            'api_token' => $metaToken,  // Will be encrypted by casting
            'device_id' => '123456789',
            'display_phone_number' => '+62 896-7934-9884',
            'graph_api_version' => 'v23.0',
            'is_default' => true,
            'status' => 'active',
            'meta_connection_status' => 'connected',
        ]);

        // Verify token is stored encrypted in DB
        $dbRow = \DB::table('whatsapp_providers')->find($provider->id);
        $this->assertNotSame($metaToken, $dbRow->api_token, 'Token should be encrypted in database');
        $this->assertTrue(str_starts_with($dbRow->api_token, 'eyJ'), 'Token should start with eyJ (encrypted JSON)');

        // Verify token is decrypted when accessed via model
        $freshProvider = WhatsAppProvider::find($provider->id);
        $this->assertSame($metaToken, $freshProvider->api_token, 'Token should be decrypted when accessed via model');

        // Mock Meta API and test broadcast with text message (not template)
        Http::fake(['*' => Http::response([
            'messages' => [
                ['id' => 'wamid_meta_123456']
            ]
        ], 200)]);

        $customer = Customer::factory()->create(['phone' => '6281111111111', 'name' => 'Test User']);

        // Create template to avoid template requirement error
        $template = WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'name' => 'hello_template',
            'body' => 'Hello {{1}}, ini test dengan template',
            'body_meta' => 'Hello {{1}}, ini test dengan template',
            'language' => 'id',
            'status' => 'APPROVED',
            'category' => 'MARKETING',
        ]);

        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'target_type' => 'customer',
            'send_mode' => 'meta_template',
            'message_template' => 'Hello {{nama}}, ini test dengan template',
            'whatsapp_message_template_id' => $template->id,
            'total_recipients' => 1,
        ]);

        $recipient = WhatsAppBroadcastRecipient::create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_id' => $customer->id,
            'recipient_type' => 'customer',
            'phone_number' => '6281111111111',
            'recipient_name' => $customer->name,
            'status' => 'queued',
        ]);

        // Process job
        $job = new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id);
        $manager = $this->app->make(\App\Services\WhatsApp\WhatsAppManager::class);
        $job->handle($manager);

        // Verify recipient marked as sent
        $recipient->refresh();
        $this->assertSame('sent', $recipient->status, "Failed: {$recipient->error_message}");
        $this->assertSame('wamid_meta_123456', $recipient->provider_message_id);
        $this->assertNull($recipient->error_message);

        // Verify the correct (decrypted) token was sent in Bearer header
        Http::assertSent(function ($request) use ($metaToken) {
            // Check Authorization header contains decrypted token
            $authHeader = $request->header('Authorization');
            $this->assertNotNull($authHeader);
            
            // Handle if authHeader is array
            if (is_array($authHeader)) {
                $authHeader = $authHeader[0] ?? '';
            }
            
            $this->assertTrue(str_starts_with((string) $authHeader, 'Bearer '), 'Should have Bearer prefix');
            
            // Extract token from "Bearer {token}"
            $sentToken = str_replace('Bearer ', '', (string) $authHeader);
            
            // Verify sent token is the decrypted one, not encrypted
            $this->assertSame($metaToken, $sentToken, 'Should send decrypted token');
            $this->assertFalse(str_starts_with($sentToken, 'eyJ'), 'Should not send encrypted token');
            $this->assertFalse(str_starts_with($sentToken, 'eyJpdiI6'), 'Should not send Laravel encrypted token payload');
            
            return true;
        });
    }

    public function test_meta_oauth_error_detail_is_stored_for_failed_broadcast_recipient(): void
    {
        $provider = WhatsAppProvider::create([
            'name' => 'Meta OAuth Provider',
            'provider' => 'meta',
            'api_token' => 'EAA_plain_meta_token_for_error_test',
            'device_id' => '123456789',
            'display_phone_number' => '+62 896-7934-9884',
            'graph_api_version' => 'v23.0',
            'is_default' => true,
            'status' => 'active',
            'meta_connection_status' => 'connected',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/123456789/messages' => Http::response([
                'error' => [
                    'message' => 'Error validating access token: Session has expired.',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $customer = Customer::factory()->create([
            'phone' => '6281111111111',
            'name' => 'Test User',
        ]);

        $template = WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'name' => 'crm_test',
            'body' => 'Halo {{1}}, ini test dengan template',
            'body_meta' => 'Halo {{1}}, ini test dengan template',
            'language' => 'id',
            'status' => 'APPROVED',
            'category' => 'UTILITY',
            'is_default' => true,
        ]);

        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'target_type' => 'customer',
            'send_mode' => 'meta_template',
            'message_template' => 'Halo {{nama}}, ini test dengan template',
            'whatsapp_message_template_id' => $template->id,
            'total_recipients' => 1,
        ]);

        $recipient = WhatsAppBroadcastRecipient::create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_id' => $customer->id,
            'recipient_type' => 'customer',
            'phone_number' => '6281111111111',
            'recipient_name' => $customer->name,
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))
            ->handle($this->app->make(\App\Services\WhatsApp\WhatsAppManager::class));

        $recipient->refresh();

        $this->assertSame('failed', $recipient->status);
        $this->assertNull($recipient->provider_message_id);
        $this->assertNotSame('Authentication Error', $recipient->error_message);
        $this->assertStringContainsString('Error validating access token', $recipient->error_message);
        $this->assertStringContainsString('Code: 190', $recipient->error_message);
        $this->assertStringContainsString('Type: OAuthException', $recipient->error_message);
        $this->assertSame($recipient->error_message, $recipient->failed_reason);
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

    protected function fakeFailedProviderRawMetaError(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.fonnte.test/send' => Http::response([
                'status' => false,
                'raw' => [
                    'error' => [
                        'message' => 'Template not approved',
                    ],
                ],
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
