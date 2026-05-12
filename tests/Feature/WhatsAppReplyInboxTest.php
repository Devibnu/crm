<?php

namespace Tests\Feature;

use App\Models\OmnichannelMessage;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastReply;
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

        OmnichannelMessage::factory()->create([
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'sender_name' => 'Omnichannel Sender',
            'sender_contact' => '081200000002',
            'message' => 'Reply from omnichannel source',
            'status' => 'resolved',
        ]);

        $this->get(route('admin.marketing.whatsapp-replies.index'))
            ->assertOk()
            ->assertSee('WhatsApp Reply Inbox')
            ->assertSee('Broadcast Sender')
            ->assertSee('Reply from broadcast source')
            ->assertSee('Reply Source Campaign')
            ->assertSee('Omnichannel Sender')
            ->assertSee('Reply from omnichannel source')
            ->assertSee('Omnichannel WhatsApp')
            ->assertSee('Total Replies')
            ->assertSee('Sender Name')
            ->assertSee('Phone Number')
            ->assertSee('Related Campaign');
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
}