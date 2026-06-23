<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsAppOmnichannelInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'webhook_secret' => 'omnichannel-secret',
            'status' => 'inactive',
            'is_default' => false,
        ]);
        $this->withHeader('X-Webhook-Secret', 'omnichannel-secret');
    }

    public function test_webhook_inbound_creates_conversation_lead_and_message_without_customer(): void
    {
        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081234560001',
            'name' => 'Customer WhatsApp',
            'message' => 'Halo admin',
            'id' => 'fonnte-in-1',
        ])->assertOk();

        $lead = Lead::query()->where('whatsapp', '6281234560001')->firstOrFail();

        $this->assertDatabaseMissing('customers', [
            'whatsapp' => '6281234560001',
        ]);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'customer_id' => null,
            'lead_id' => $lead->id,
            'phone_number' => '6281234560001',
            'channel' => 'whatsapp',
            'status' => 'open',
            'last_message' => 'Halo admin',
            'unread_count' => 1,
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'customer_id' => null,
            'lead_id' => $lead->id,
            'phone' => '6281234560001',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Halo admin',
            'provider_message_id' => 'fonnte-in-1',
            'provider' => 'fonnte',
            'status' => 'delivered',
        ]);
    }

    public function test_webhook_inbound_appends_existing_conversation_without_duplicate(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Existing Customer',
            'phone' => '628111222333',
            'whatsapp' => '628111222333',
        ]);
        WhatsAppConversation::create([
            'customer_id' => $customer->id,
            'contact_name' => $customer->name,
            'phone_number' => '628111222333',
            'channel' => 'whatsapp',
            'last_message' => 'Old',
            'last_message_at' => now()->subHour(),
            'unread_count' => 2,
            'status' => 'open',
        ]);

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '+628111222333',
            'name' => 'Existing Customer',
            'message' => 'Pesan baru',
        ])->assertOk();

        $this->assertDatabaseCount('whatsapp_conversations', 1);
        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone_number' => '628111222333',
            'last_message' => 'Pesan baru',
            'unread_count' => 3,
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '628111222333',
            'direction' => 'inbound',
            'message' => 'Pesan baru',
        ]);
    }

    public function test_admin_outbound_reply_is_sent_and_saved(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.fonnte.test/send' => Http::response([
                'status' => true,
                'id' => 'out-1',
            ]),
        ]);
        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://api.fonnte.test',
            'api_token' => 'dummy-token',
        ]);
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Reply Customer',
            'phone_number' => '628111222333',
            'channel' => 'whatsapp',
            'last_message' => 'Inbound',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'message' => 'Baik, kami bantu cek.',
        ])->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]));

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628111222333',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Baik, kami bantu cek.',
            'provider_message_id' => 'out-1',
            'provider' => 'fonnte',
            'status' => 'sent',
        ]);
        $this->assertSame('Baik, kami bantu cek.', $conversation->fresh()->last_message);
    }

    public function test_broadcast_reply_is_linked_to_conversation_message(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '628555111222',
            'whatsapp' => '628555111222',
        ]);
        $broadcast = WhatsAppBroadcast::factory()->create(['status' => 'completed']);
        WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_type' => 'customer',
            'recipient_id' => $customer->id,
            'phone_number' => '+62 855-5111-222',
            'status' => 'sent',
        ]);

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '08555111222',
            'name' => $customer->name,
            'message' => 'Saya balas broadcast',
        ])->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '628555111222',
            'broadcast_id' => $broadcast->id,
            'direction' => 'inbound',
            'message' => 'Saya balas broadcast',
        ]);
        $this->assertSame('completed', $broadcast->fresh()->status);
    }

    public function test_meta_webhook_incoming_text_creates_conversation(): void
    {
        $this->postMetaWebhook($this->metaInboundPayload(
            messageId: 'wamid.meta-in-1',
            phone: '628777000111',
            name: 'Meta Inbox Customer',
            body: 'Halo dari WhatsApp Meta',
        ))->assertOk();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone_number' => '628777000111',
            'contact_name' => 'Meta Inbox Customer',
            'channel' => 'whatsapp',
            'status' => 'open',
            'last_message' => 'Halo dari WhatsApp Meta',
            'unread_count' => 1,
        ]);
        $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.meta-in-1')->firstOrFail();
        $this->assertSame('628777000111', $message->phone);
        $this->assertSame('inbound', $message->direction);
        $this->assertContains($message->message_type, ['text', 'inbound']);
        $this->assertSame('Halo dari WhatsApp Meta', $message->message);
        $this->assertSame('meta', $message->provider);
        $this->assertSame('delivered', $message->status);
    }

    public function test_meta_duplicate_webhook_is_ignored(): void
    {
        $payload = $this->metaInboundPayload(
            messageId: 'wamid.meta-duplicate',
            phone: '628777000222',
            name: 'Duplicate Customer',
            body: 'Pesan sekali saja',
        );

        $this->postMetaWebhook($payload)->assertOk();
        $this->postMetaWebhook($payload)
            ->assertOk()
            ->assertJsonPath('created.0.duplicate', true);

        $this->assertSame(1, WhatsAppMessage::query()
            ->where('provider', 'meta')
            ->where('provider_message_id', 'wamid.meta-duplicate')
            ->count());
        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone_number' => '628777000222',
            'unread_count' => 1,
        ]);
    }

    public function test_meta_reply_from_crm_is_sent_and_stored(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messages' => [
                    ['id' => 'wamid.meta-out-1'],
                ],
            ], 200),
        ]);
        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
        ]);
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Meta Reply Customer',
            'phone_number' => '628777000333',
            'channel' => 'whatsapp',
            'last_message' => 'Inbound',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'message' => 'Baik, pesan diterima.',
        ])->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]));

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Baik, pesan diterima.');
        $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.meta-out-1')->firstOrFail();
        $this->assertSame($conversation->id, $message->whatsapp_conversation_id);
        $this->assertSame('628777000333', $message->phone);
        $this->assertSame('outbound', $message->direction);
        $this->assertContains($message->message_type, ['text', 'outbound']);
        $this->assertSame('Baik, pesan diterima.', $message->message);
        $this->assertSame('meta', $message->provider);
        $this->assertSame('sent', $message->status);
        $this->assertSame('Baik, pesan diterima.', $conversation->fresh()->last_message);
    }

    public function test_omnichannel_timeline_displays_messages_and_ticket_created_event(): void
    {
        $lead = Lead::factory()->create(['name' => 'Timeline Lead']);
        $conversation = WhatsAppConversation::create([
            'lead_id' => $lead->id,
            'contact_name' => 'Timeline Customer',
            'phone_number' => '628777009999',
            'channel' => 'whatsapp',
            'last_message' => 'Butuh bantuan invoice',
            'last_message_at' => now(),
            'status' => 'open',
            'assigned_to' => 'Support Agent',
            'taken_at' => now(),
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628777009999',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Template: promo follow up',
            'status' => 'sent',
            'provider' => 'meta',
            'sent_at' => now()->subMinutes(5),
        ]);
        $inbound = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'lead_id' => $lead->id,
            'phone' => '628777009999',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Butuh bantuan invoice',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);
        $ticket = Ticket::factory()->create([
            'lead_id' => $lead->id,
            'whatsapp_message_id' => $inbound->id,
            'source_type' => 'whatsapp_message',
            'source_id' => $inbound->id,
            'subject' => 'Invoice timeline issue',
            'channel' => 'whatsapp',
        ]);
        $inbound->update(['ticket_id' => $ticket->id]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('CRM Timeline')
            ->assertSee('Broadcast Sent')
            ->assertSee('Customer Reply')
            ->assertSee('Converted To Lead')
            ->assertSee('Ticket Created')
            ->assertSee($ticket->ticket_number)
            ->assertSee('Conversation Assigned')
            ->assertSeeInOrder(['Customer Reply', 'Broadcast Sent']);
    }

    public function test_omnichannel_customer_360_workspace_displays_crm_context_and_filters(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Workspace Customer',
            'phone' => '628777001234',
            'whatsapp' => '628777001234',
        ]);
        $lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Workspace Lead',
            'whatsapp' => '628777001234',
        ]);
        $conversation = WhatsAppConversation::create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'contact_name' => 'Workspace Contact',
            'phone_number' => '628777001234',
            'channel' => 'whatsapp',
            'last_message' => 'Need workspace details',
            'last_message_at' => now(),
            'unread_count' => 3,
            'status' => 'open',
            'priority' => 'high',
            'assigned_to' => 'Agent A',
        ]);
        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'phone' => '628777001234',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Need workspace details',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'whatsapp_message_id' => $message->id,
            'ticket_number' => 'TCK-WORKSPACE-001',
            'subject' => 'Workspace ticket',
            'channel' => 'whatsapp',
        ]);
        $message->update(['ticket_id' => $ticket->id]);
        $opportunity = Opportunity::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'title' => 'Workspace Opportunity',
            'status' => 'open',
        ]);
        Quotation::factory()->create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QTN-WORKSPACE-001',
            'title' => 'Workspace Quotation',
        ]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Semua')
            ->assertSee('Belum Diambil')
            ->assertSee('Milik Saya')
            ->assertSee('Open')
            ->assertSee('Resolved')
            ->assertSee('Select Conversations')
            ->assertSee('Workspace Contact')
            ->assertSee('Assigned')
            ->assertSee('CONTACT INFORMATION')
            ->assertSee('CURRENT STAGE')
            ->assertSee('Lead Created')
            ->assertSee('Workspace Customer')
            ->assertSee('Workspace Lead')
            ->assertSee('Lifecycle')
            ->assertSee('Customer')
            ->assertSee('ACTION')
            ->assertSee('Create Ticket')
            ->assertSee('Create Lead')
            ->assertSee('Open Customer')
            ->assertSee('Open Lead')
            ->assertSee('CRM Timeline')
            ->assertSee('Customer Reply')
            ->assertSee('Converted To Lead')
            ->assertSee('Ticket Created')
            ->assertSee('RECENT CRM DATA')
            ->assertSee('Recent Ticket')
            ->assertSee('TCK-WORKSPACE-001')
            ->assertSee('Recent Opportunity')
            ->assertSee('Workspace Opportunity')
            ->assertSee('Recent Quotation')
            ->assertSee('QTN-WORKSPACE-001')
            ->assertSee('Last Activity:')
            ->assertSee('Terima kasih')
            ->assertSee('Baik, kami cek terlebih dahulu')
            ->assertSee('Mohon tunggu sebentar')
            ->assertSee('Tim kami akan menghubungi Anda')
            ->assertSee('Hari Ini');
    }

    public function test_omnichannel_workspace_shows_lead_prospect_label_for_new_whatsapp_lead(): void
    {
        $lead = Lead::factory()->create([
            'customer_id' => null,
            'name' => 'Prospect WhatsApp',
            'phone' => '628777001235',
            'whatsapp' => '628777001235',
            'source' => 'whatsapp',
            'lead_source' => 'whatsapp',
        ]);
        $conversation = WhatsAppConversation::create([
            'customer_id' => null,
            'lead_id' => $lead->id,
            'contact_name' => 'Prospect WhatsApp',
            'phone_number' => '628777001235',
            'channel' => 'whatsapp',
            'last_message' => 'Saya tertarik',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => null,
            'lead_id' => $lead->id,
            'phone' => '628777001235',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Saya tertarik',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Prospect WhatsApp')
            ->assertSee('Lifecycle')
            ->assertSee('Lead / Prospect')
            ->assertSee('Open Lead')
            ->assertDontSee('Open Customer');
    }

    public function test_omnichannel_reply_form_is_ready_for_attachment_uploads(): void
    {
        $conversation = $this->conversationWithInboundMessage('Attachment Form Customer', '6287770003331');

        $response = $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee(route('admin.service.omnichannel.reply', $conversation), false)
            ->assertSee('type="button" class="omni-icon-btn" title="Emoji" data-omni-emoji-button', false)
            ->assertSee('data-omni-emoji-picker', false)
            ->assertSee('emoji-picker-element@1/index.js', false)
            ->assertSee('<emoji-picker data-omni-emoji-element></emoji-picker>', false)
            ->assertSee("emojiPicker?.addEventListener('emoji-click'", false)
            ->assertSee('event.detail?.unicode', false)
            ->assertSee('textarea name="message"', false)
            ->assertSee('data-omni-message-input', false)
            ->assertSee('type="button" class="omni-icon-btn" title="Attachment" data-omni-attachment-button', false)
            ->assertSee('type="file" name="attachment" data-omni-attachment-input hidden', false)
            ->assertSee('accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.mp3"', false)
            ->assertSee('data-omni-attachment-pill hidden', false)
            ->assertSee('data-omni-attachment-clear', false)
            ->assertSee('data-omni-select-mode', false)
            ->assertSee("classList.toggle('is-selecting')", false)
            ->assertSee('data-omni-quick-reply', false)
            ->assertSee('setSelectionRange', false)
            ->assertSee('event.preventDefault();', false)
            ->assertSee('event.stopPropagation();', false)
            ->assertSee('isEmojiPickerOpen', false)
            ->assertSee('hasSelectedAttachment', false);

        $content = $response->getContent();
        $formPosition = strpos($content, 'class="omni-composer"');
        $inputPosition = strpos($content, 'name="attachment"');
        $formEndPosition = strpos($content, '</form>', $formPosition);
        $composerHtml = substr($content, $formPosition, $formEndPosition - $formPosition);
        $pickerPosition = strpos($content, 'data-omni-emoji-picker');
        $pickerEndPosition = strpos($content, '</div>', $pickerPosition);
        $pickerHtml = substr($content, $pickerPosition, $pickerEndPosition - $pickerPosition);

        $this->assertNotFalse($formPosition);
        $this->assertNotFalse($inputPosition);
        $this->assertNotFalse($formEndPosition);
        $this->assertNotFalse($pickerPosition);
        $this->assertNotFalse($pickerEndPosition);
        $this->assertGreaterThan($formPosition, $inputPosition);
        $this->assertLessThan($formEndPosition, $inputPosition);
        $this->assertSame(0, preg_match('/<button(?![^>]*\btype=)[^>]*>/', $composerHtml));
        $this->assertStringContainsString('data-omni-emoji-button', $composerHtml);
        $this->assertStringContainsString('type="button" class="omni-icon-btn" title="Emoji"', $composerHtml);
        $this->assertStringContainsString('data-omni-attachment-button', $composerHtml);
        $this->assertStringContainsString('type="button" class="omni-icon-btn" title="Attachment"', $composerHtml);
        $this->assertStringContainsString('type="button" class="omni-attachment-clear"', $composerHtml);
        $this->assertStringContainsString('type="submit" class="btn btn-primary"', $composerHtml);
        $this->assertStringContainsString('emoji-picker', $pickerHtml);
    }

    public function test_admin_can_upload_image_attachment_to_meta_and_store_media_message(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/media' => Http::response([
                'id' => 'media-image-1',
            ], 200),
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messages' => [
                    ['id' => 'wamid.media-image-out-1'],
                ],
            ], 200),
        ]);
        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
        ]);
        $conversation = $this->conversationWithInboundMessage('Image Upload Customer', '628777000334');

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'message' => 'Foto bukti',
            'attachment' => UploadedFile::fake()->create('bukti.jpg', 128, 'image/jpeg'),
        ])->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]));

        $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.media-image-out-1')->firstOrFail();

        Storage::disk('public')->assertExists($message->media_path);
        $this->assertSame($conversation->id, $message->whatsapp_conversation_id);
        $this->assertContains($message->message_type, ['image', 'outbound']);
        $this->assertSame('Foto bukti', $message->message);
        $this->assertSame('bukti.jpg', $message->media_original_name);
        $this->assertSame('image/jpeg', $message->media_mime);
        $this->assertSame('media-image-1', $message->media_id);
        $this->assertSame('sent', $message->status);
        $this->assertSame('Foto bukti', $conversation->fresh()->last_message);
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v23.0/1234567890/media');
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
            && $request['type'] === 'image'
            && $request['image']['id'] === 'media-image-1'
            && $request['image']['caption'] === 'Foto bukti');
    }

    public function test_admin_can_upload_document_attachment_to_meta_and_store_media_message(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/media' => Http::response([
                'id' => 'media-document-1',
            ], 200),
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messages' => [
                    ['id' => 'wamid.media-document-out-1'],
                ],
            ], 200),
        ]);
        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
        ]);
        $conversation = $this->conversationWithInboundMessage('Document Upload Customer', '628777000335');

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'attachment' => UploadedFile::fake()->create('invoice.pdf', 256, 'application/pdf'),
        ])->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]));

        $message = WhatsAppMessage::query()->where('provider_message_id', 'wamid.media-document-out-1')->firstOrFail();

        Storage::disk('public')->assertExists($message->media_path);
        $this->assertContains($message->message_type, ['document', 'outbound']);
        $this->assertSame('invoice.pdf', $message->message);
        $this->assertSame('invoice.pdf', $message->media_original_name);
        $this->assertSame('application/pdf', $message->media_mime);
        $this->assertSame('media-document-1', $message->media_id);
        $this->assertSame('sent', $message->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
            && $request['type'] === 'document'
            && $request['document']['id'] === 'media-document-1'
            && $request['document']['filename'] === 'invoice.pdf');
    }

    public function test_media_reply_is_stored_as_failed_when_meta_media_upload_fails(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/media' => Http::response([
                'error' => ['message' => 'Unsupported media upload'],
            ], 400),
        ]);
        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
        ]);
        $conversation = $this->conversationWithInboundMessage('Failed Media Customer', '628777000336');

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'attachment' => UploadedFile::fake()->create('failed.pdf', 128, 'application/pdf'),
        ])->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertSessionHas('error');

        $message = WhatsAppMessage::query()->where('media_original_name', 'failed.pdf')->firstOrFail();

        $this->assertSame('failed', $message->status);
        $this->assertSame('Unsupported media upload | HTTP: 400', $message->error_message);
        $this->assertNull($message->provider_message_id);
        $this->assertNull($message->media_id);
        Http::assertNotSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages');
    }

    public function test_attachment_validation_rejects_dangerous_extension(): void
    {
        $conversation = $this->conversationWithInboundMessage('Dangerous Upload Customer', '628777000337');

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'attachment' => UploadedFile::fake()->create('payload.php', 1, 'application/x-php'),
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseMissing('whatsapp_messages', [
            'media_original_name' => 'payload.php',
        ]);
    }

    public function test_attachment_validation_rejects_file_larger_than_ten_mb(): void
    {
        $conversation = $this->conversationWithInboundMessage('Large Upload Customer', '628777000338');

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'attachment' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseMissing('whatsapp_messages', [
            'media_original_name' => 'large.pdf',
        ]);
    }

    public function test_reply_without_text_and_without_attachment_is_invalid(): void
    {
        $conversation = $this->conversationWithInboundMessage('Empty Reply Customer', '6287770003381');

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'message' => '',
        ])->assertSessionHasErrors('message');

        $this->assertDatabaseMissing('whatsapp_messages', [
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
        ]);
    }

    public function test_omnichannel_thread_renders_attachment_preview_and_download_link(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('whatsapp-attachments/rendered-image.jpg', 'image-bytes');
        Storage::disk('public')->put('whatsapp-attachments/rendered-document.pdf', 'pdf-bytes');
        Storage::disk('public')->put('whatsapp-attachments/rendered-video.mp4', 'video-bytes');
        $conversation = $this->conversationWithInboundMessage('Rendered Attachment Customer', '628777000339');
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628777000339',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Preview image',
            'provider' => 'meta',
            'status' => 'sent',
            'sent_at' => now(),
            'media_path' => 'whatsapp-attachments/rendered-image.jpg',
            'media_original_name' => 'rendered-image.jpg',
            'media_mime' => 'image/jpeg',
            'media_size' => 10,
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628777000339',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Document',
            'provider' => 'meta',
            'status' => 'sent',
            'sent_at' => now(),
            'media_path' => 'whatsapp-attachments/rendered-document.pdf',
            'media_original_name' => 'rendered-document.pdf',
            'media_mime' => 'application/pdf',
            'media_size' => 10,
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628777000339',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Video',
            'provider' => 'meta',
            'status' => 'sent',
            'sent_at' => now(),
            'media_path' => 'whatsapp-attachments/rendered-video.mp4',
            'media_original_name' => 'rendered-video.mp4',
            'media_mime' => 'video/mp4',
            'media_size' => 10,
        ]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('.omni-bubble{max-width:min(320px,78%);padding:8px 10px;overflow:hidden}', false)
            ->assertSee('omni-media-preview', false)
            ->assertSee('max-width:min(260px,100%);max-height:180px', false)
            ->assertSee('rendered-image.jpg')
            ->assertSee('omni-media-video', false)
            ->assertSee('max-width:260px;max-height:160px', false)
            ->assertSee('rendered-video.mp4')
            ->assertSee('omni-media-file', false)
            ->assertSee('grid-template-columns:2.25rem minmax(0,1fr)', false)
            ->assertSee('text-overflow:ellipsis', false)
            ->assertSee('rendered-document.pdf')
            ->assertSee('/storage/whatsapp-attachments/rendered-image.jpg', false)
            ->assertSee('/storage/whatsapp-attachments/rendered-document.pdf', false)
            ->assertSee('/storage/whatsapp-attachments/rendered-video.mp4', false);
    }

    public function test_assign_and_resolve_conversation(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Assignable Customer',
            'phone_number' => '628777000444',
            'channel' => 'whatsapp',
            'last_message' => 'Need help',
            'last_message_at' => now(),
            'status' => 'open',
            'unread_count' => 3,
        ]);

        $this->post(route('admin.service.omnichannel.assign', $conversation))
            ->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]));

        $conversation->refresh();
        $this->assertSame(auth()->user()->name, $conversation->assigned_to);
        $this->assertNotNull($conversation->taken_at);

        $this->post(route('admin.service.omnichannel.resolve', $conversation))
            ->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]));

        $conversation->refresh();
        $this->assertContains($conversation->status, ['resolved', 'closed']);
        $this->assertSame(0, $conversation->unread_count);
        $this->assertNotNull($conversation->closed_at);
    }

    public function test_assigning_conversation_refreshes_taken_state_and_filters(): void
    {
        $conversation = $this->conversationWithInboundMessage('Take State Customer', '628777000443');

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Ambil')
            ->assertSee('Ambil Conversation')
            ->assertSee('Belum Diambil');

        $this->post(route('admin.service.omnichannel.assign', $conversation))
            ->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]));

        $conversation->refresh();
        $this->assertSame(auth()->user()->name, $conversation->assigned_to);
        $this->assertNotNull($conversation->taken_at);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Take State Customer')
            ->assertSee('Conversation Assigned')
            ->assertSee(auth()->user()->name)
            ->assertSee('Sudah diambil oleh ' . auth()->user()->name)
            ->assertDontSee('>Ambil</button>', false)
            ->assertDontSee('>Ambil Conversation</button>', false);

        $this->get(route('admin.service.omnichannel.index', ['filter' => 'milik-saya', 'conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Take State Customer');

        $this->get(route('admin.service.omnichannel.index', ['filter' => 'belum-diambil']))
            ->assertOk()
            ->assertDontSee('Take State Customer');
    }

    public function test_opening_conversation_marks_internal_unread_count_as_read(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Unread Customer',
            'phone_number' => '628777000445',
            'channel' => 'whatsapp',
            'last_message' => 'Need admin read',
            'last_message_at' => now(),
            'status' => 'open',
            'unread_count' => 5,
        ]);
        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628777000445',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Need admin read',
            'provider' => 'meta',
            'provider_message_id' => 'wamid.unread-count-test',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Unread Customer')
            ->assertDontSee('<b>5</b>', false);

        $conversation->refresh();
        $message->refresh();

        $this->assertSame(0, $conversation->unread_count);
        $this->assertSame('delivered', $message->status);
        $this->assertNull($message->read_at);
        $this->assertSame('wamid.unread-count-test', $message->provider_message_id);
    }

    public function test_inbox_filter_mine_and_unassigned(): void
    {
        WhatsAppConversation::create([
            'contact_name' => 'Mine Customer',
            'phone_number' => '628777000555',
            'channel' => 'whatsapp',
            'last_message' => 'Milik saya',
            'last_message_at' => now(),
            'status' => 'open',
            'assigned_to' => auth()->user()->name,
        ]);
        $mine = WhatsAppConversation::query()->where('phone_number', '628777000555')->firstOrFail();
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $mine->id,
            'phone' => '628777000555',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Milik saya',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $unassigned = WhatsAppConversation::create([
            'contact_name' => 'Unassigned Customer',
            'phone_number' => '628777000666',
            'channel' => 'whatsapp',
            'last_message' => 'Belum diambil',
            'last_message_at' => now()->subMinute(),
            'status' => 'open',
            'assigned_to' => null,
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $unassigned->id,
            'phone' => '628777000666',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Belum diambil',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $other = WhatsAppConversation::create([
            'contact_name' => 'Other Agent Customer',
            'phone_number' => '628777000777',
            'channel' => 'whatsapp',
            'last_message' => 'Milik agent lain',
            'last_message_at' => now()->subMinutes(2),
            'status' => 'open',
            'assigned_to' => 'Other Agent',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $other->id,
            'phone' => '628777000777',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Milik agent lain',
            'provider' => 'fonnte',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->get(route('admin.service.omnichannel.index', ['filter' => 'milik-saya']))
            ->assertOk()
            ->assertSee('Mine Customer')
            ->assertDontSee('Unassigned Customer')
            ->assertDontSee('Other Agent Customer');

        $this->get(route('admin.service.omnichannel.index', ['filter' => 'belum-diambil']))
            ->assertOk()
            ->assertSee('Unassigned Customer')
            ->assertDontSee('Mine Customer')
            ->assertDontSee('Other Agent Customer');
    }

    public function test_inbox_hides_demo_conversation_without_inbound_message(): void
    {
        $demo = WhatsAppConversation::create([
            'contact_name' => 'Muchtadi',
            'phone_number' => '628777000888',
            'channel' => 'whatsapp',
            'last_message' => 'Template: promo',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $demo->id,
            'phone' => '628777000888',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Template: promo',
            'provider' => 'meta',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $real = WhatsAppConversation::create([
            'contact_name' => 'Real Meta Customer',
            'phone_number' => '628777000889',
            'channel' => 'whatsapp',
            'last_message' => 'Real inbound',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $real->id,
            'phone' => '628777000889',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Real inbound',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->get(route('admin.service.omnichannel.index'))
            ->assertOk()
            ->assertSee('Real Meta Customer')
            ->assertSee('Meta Cloud API')
            ->assertDontSee('Muchtadi')
            ->assertDontSee('Template: promo');
    }

    public function test_admin_can_delete_conversation(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Delete Me',
            'phone_number' => '628777000990',
            'channel' => 'whatsapp',
            'last_message' => 'hapus',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628777000990',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'hapus',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->delete(route('admin.service.omnichannel.destroy-conversation', $conversation))
            ->assertRedirect(route('admin.service.omnichannel.index'));

        $this->assertDatabaseMissing('whatsapp_conversations', ['id' => $conversation->id]);
    }

    public function test_admin_can_bulk_delete_conversations(): void
    {
        $first = WhatsAppConversation::create([
            'contact_name' => 'Bulk Delete One',
            'phone_number' => '628777000991',
            'channel' => 'whatsapp',
            'last_message' => 'one',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $second = WhatsAppConversation::create([
            'contact_name' => 'Bulk Delete Two',
            'phone_number' => '628777000992',
            'channel' => 'whatsapp',
            'last_message' => 'two',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $this->delete(route('admin.service.omnichannel.bulk-destroy-conversations'), [
            'conversation_ids' => [$first->id, $second->id],
        ])->assertRedirect(route('admin.service.omnichannel.index'));

        $this->assertDatabaseMissing('whatsapp_conversations', ['id' => $first->id]);
        $this->assertDatabaseMissing('whatsapp_conversations', ['id' => $second->id]);
    }

    private function conversationWithInboundMessage(string $contactName, string $phone): WhatsAppConversation
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => $contactName,
            'phone_number' => $phone,
            'channel' => 'whatsapp',
            'last_message' => 'Inbound',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => $phone,
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Inbound',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        return $conversation;
    }

    private function metaInboundPayload(string $messageId, string $phone, string $name, string $body): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba-1',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'contacts' => [
                                    [
                                        'profile' => ['name' => $name],
                                        'wa_id' => $phone,
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => $phone,
                                        'id' => $messageId,
                                        'timestamp' => '1780732800',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => $body,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function postMetaWebhook(array $payload, string $secret = 'meta-omnichannel-secret'): TestResponse
    {
        config(['services.whatsapp.meta_app_secret' => $secret]);

        if (! WhatsAppProvider::query()->where('provider', 'meta')->exists()) {
            WhatsAppProvider::factory()->create([
                'provider' => 'meta',
                'webhook_secret' => 'verify-token',
                'status' => 'active',
                'is_default' => false,
            ]);
        }

        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

        return $this->withHeader('X-Hub-Signature-256', $signature)
            ->postJson(route('webhooks.whatsapp.meta'), $payload);
    }
}
