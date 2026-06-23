<?php

namespace App\Events\Omnichannel\Concerns;

use Illuminate\Broadcasting\PrivateChannel;

trait BroadcastsOmnichannelEvent
{
    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('omnichannel'),
            new PrivateChannel('omnichannel.conversation.'.$this->conversationId),
        ];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId ?? null,
            'note_id' => $this->noteId ?? null,
            'status' => $this->status ?? null,
            'assigned_to' => $this->assignedTo ?? null,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
