<?php

namespace Tests\Feature;

use App\Events\Omnichannel\ConversationNoteCreated;
use App\Events\Omnichannel\MessageReceived;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class OmnichannelBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'testing-reverb-key',
            'broadcasting.connections.reverb.secret' => 'testing-reverb-secret',
            'broadcasting.connections.reverb.app_id' => 'testing-reverb-app',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
    }

    public function test_user_with_omnichannel_view_can_authorize_private_omnichannel_channel(): void
    {
        $user = User::factory()->create(['email' => 'omnichannel-viewer@example.com']);
        $user->assignRole('support');

        $this->assertTrue($user->can('omnichannel.view'));

        $this->actingAs($user)->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-omnichannel',
        ])->assertOk();
    }

    public function test_user_without_omnichannel_view_cannot_authorize_private_omnichannel_channel(): void
    {
        $user = User::factory()->create(['email' => 'no-omnichannel-permission@example.com']);
        $user->syncRoles([]);

        $this->assertFalse($user->can('omnichannel.view'));

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-omnichannel',
            ])
            ->assertForbidden();
    }

    public function test_omnichannel_events_broadcast_to_workspace_and_conversation_channels(): void
    {
        $event = new MessageReceived(conversationId: 15, messageId: 99);

        $this->assertSame('MessageReceived', $event->broadcastAs());
        $this->assertSame([
            'private-omnichannel',
            'private-omnichannel.conversation.15',
        ], array_map('strval', $event->broadcastOn()));
        $payload = $event->broadcastWith();

        $this->assertSame([
            'conversation_id' => 15,
            'message_id' => 99,
            'note_id' => null,
            'status' => null,
            'assigned_to' => null,
        ], collect($payload)->except('occurred_at')->all());
        $this->assertIsString($payload['occurred_at']);
    }

    public function test_internal_note_event_broadcasts_note_payload(): void
    {
        $event = new ConversationNoteCreated(conversationId: 21, noteId: 44);

        $this->assertSame('ConversationNoteCreated', $event->broadcastAs());
        $this->assertSame([
            'private-omnichannel',
            'private-omnichannel.conversation.21',
        ], array_map('strval', $event->broadcastOn()));
        $this->assertSame(44, $event->broadcastWith()['note_id']);
    }
}
