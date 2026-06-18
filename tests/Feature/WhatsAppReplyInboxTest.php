<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastReply;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppReplyInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_inbox_displays_real_data_from_broadcast_and_omnichannel(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create([
            'name' => 'Reply Source Campaign',
        ]);

        WhatsAppBroadcastReply::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'sender_name' => 'Broadcast Sender',
            'phone_number' => '081200000001',
            'message' => 'Reply from broadcast source',
            'status' => 'unread',
        ]);

        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Donny',
            'phone_number' => '081200000002',
            'channel' => 'whatsapp',
            'last_message' => 'Reply from omnichannel source',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '081200000002',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Reply from omnichannel source',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);

        $this->get(route('admin.marketing.whatsapp-replies.index'))
            ->assertOk()
            ->assertSee('WhatsApp Reply Inbox')
            ->assertSee('Broadcast Sender')
            ->assertSee('Reply from broadcast source')
            ->assertSee('Reply Source Campaign')
            ->assertSee('Donny')
            ->assertSee('Reply from omnichannel source')
            ->assertSee('Omnichannel WhatsApp')
            ->assertSee('Broadcast')
            ->assertSee('Omnichannel')
            ->assertSee('Total Replies')
            ->assertSee('Sender')
            ->assertSee('Campaign')
            ->assertSee('Source')
            ->assertSee('Reply Type')
            ->assertSee('Sentiment')
            ->assertSee('Action Status');
    }

    public function test_reply_inbox_search_and_filters_work(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create([
            'name' => 'Filtered Campaign',
        ]);

        WhatsAppBroadcastReply::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'sender_name' => 'Filter Match Sender',
            'phone_number' => '081211110000',
            'message' => 'Need callback soon',
            'status' => 'unread',
        ]);

        WhatsAppBroadcastReply::factory()->create([
            'sender_name' => 'Other Sender',
            'message' => 'General chat',
            'status' => 'read',
        ]);

        $this->get(route('admin.marketing.whatsapp-replies.index', [
            'q' => 'callback',
            'status' => 'unread',
            'campaign' => 'Filtered Campaign',
        ]))
            ->assertOk()
            ->assertSee('Filter Match Sender')
            ->assertDontSee('Other Sender');
    }

    public function test_reply_inbox_auto_classifies_replies_by_keyword(): void
    {
        WhatsAppBroadcastReply::factory()->create([
            'sender_name' => 'Lead Reply',
            'message' => 'Saya tertarik, minta penawaran untuk produk ini',
        ]);

        WhatsAppBroadcastReply::factory()->create([
            'sender_name' => 'Support Reply',
            'message' => 'Ada kendala invoice dan masalah pembayaran',
        ]);

        WhatsAppBroadcastReply::factory()->create([
            'sender_name' => 'Opt Out Reply',
            'message' => 'Stop jangan kirim lagi',
        ]);

        $this->get(route('admin.marketing.whatsapp-replies.index'))
            ->assertOk()
            ->assertSee('Lead Replies')
            ->assertSee('Support Replies')
            ->assertSee('Unsubscribe Replies')
            ->assertSee('Lead')
            ->assertSee('Support')
            ->assertSee('Unsubscribe')
            ->assertSee('Positive')
            ->assertSee('Negative')
            ->assertSee('New Lead')
            ->assertSee('Send To Omnichannel')
            ->assertSee('Opt Out');
    }

    public function test_meta_inbound_message_is_classified_and_links_to_omnichannel(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Donny General',
            'phone_number' => '6281200033333',
            'channel' => 'whatsapp',
            'last_message' => 'Saya tertarik, berapa harga paketnya?',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6281200033333',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Saya tertarik, berapa harga paketnya?',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);

        $this->get(route('admin.marketing.whatsapp-replies.index'))
            ->assertOk()
            ->assertSee('Donny General')
            ->assertSee('Omnichannel WhatsApp')
            ->assertSee('Omnichannel')
            ->assertSee('Lead')
            ->assertSee('Positive')
            ->assertSee('New Lead')
            ->assertSee('Open Omnichannel')
            ->assertSee('/admin/service/omnichannel?conversation='.$conversation->id, false);
    }

    public function test_convert_to_lead_creates_lead_and_updates_action_status(): void
    {
        $reply = WhatsAppBroadcastReply::factory()->create([
            'sender_name' => 'Interested Buyer',
            'phone_number' => '6281200011111',
            'message' => 'Saya tertarik berapa harga paketnya?',
        ]);

        $this->post(route('admin.marketing.whatsapp-replies.convert-to-lead', $reply))
            ->assertRedirect(route('admin.marketing.whatsapp-replies.index'));

        $this->assertDatabaseHas('leads', [
            'name' => 'Interested Buyer',
            'whatsapp' => '6281200011111',
            'lead_source' => 'whatsapp_reply_inbox',
        ]);

        $this->assertDatabaseHas('whatsapp_broadcast_replies', [
            'id' => $reply->id,
            'reply_type' => 'lead',
            'action_status' => 'follow_up_sales',
        ]);
    }

    public function test_convert_to_lead_from_whatsapp_message_creates_or_reuses_lead_without_duplicate_conversation(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Existing WhatsApp Sender',
            'phone_number' => '6281200044444',
            'channel' => 'whatsapp',
            'last_message' => 'Hubungi saya untuk detail paket',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6281200044444',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Hubungi saya untuk detail paket',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);
        Lead::factory()->create([
            'name' => 'Existing Lead',
            'phone' => '6281200044444',
            'whatsapp' => '6281200044444',
        ]);

        $this->post(route('admin.marketing.whatsapp-replies.messages.convert-to-lead', $message))
            ->assertRedirect(route('admin.marketing.whatsapp-replies.index'));

        $this->assertSame(1, Lead::query()->where('whatsapp', '6281200044444')->count());
        $this->assertSame(1, WhatsAppConversation::query()->where('phone_number', '6281200044444')->count());
        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $message->id,
            'status' => 'read',
        ]);
    }

    public function test_send_to_omnichannel_creates_conversation_and_message(): void
    {
        $reply = WhatsAppBroadcastReply::factory()->create([
            'sender_name' => 'Support Customer',
            'phone_number' => '6281200022222',
            'message' => 'Ada kendala invoice mohon dibantu',
        ]);

        $this->post(route('admin.marketing.whatsapp-replies.send-to-omnichannel', $reply))
            ->assertRedirect(route('admin.marketing.whatsapp-replies.index'));

        $conversation = WhatsAppConversation::query()
            ->where('phone_number', '6281200022222')
            ->firstOrFail();

        $this->assertSame('Support Customer', $conversation->contact_name);
        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6281200022222',
            'message_type' => 'inbound',
            'message' => 'Ada kendala invoice mohon dibantu',
        ]);
        $this->assertDatabaseHas('whatsapp_broadcast_replies', [
            'id' => $reply->id,
            'action_status' => 'send_to_omnichannel',
        ]);
    }

    public function test_mark_closed_updates_reply_action_status(): void
    {
        $reply = WhatsAppBroadcastReply::factory()->create([
            'message' => 'General reply',
            'status' => 'unread',
        ]);

        $this->post(route('admin.marketing.whatsapp-replies.mark-closed', $reply))
            ->assertRedirect(route('admin.marketing.whatsapp-replies.index'));

        $this->assertDatabaseHas('whatsapp_broadcast_replies', [
            'id' => $reply->id,
            'status' => 'resolved',
            'action_status' => 'closed',
        ]);
    }

    public function test_mark_closed_for_whatsapp_message_marks_message_read_without_new_conversation(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Read Only Sender',
            'phone_number' => '6281200055555',
            'channel' => 'whatsapp',
            'last_message' => 'General reply',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6281200055555',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'General reply',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);

        $this->post(route('admin.marketing.whatsapp-replies.messages.mark-closed', $message))
            ->assertRedirect(route('admin.marketing.whatsapp-replies.index'));

        $this->assertSame(1, WhatsAppConversation::query()->where('phone_number', '6281200055555')->count());
        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $message->id,
            'status' => 'read',
        ]);
    }
}
