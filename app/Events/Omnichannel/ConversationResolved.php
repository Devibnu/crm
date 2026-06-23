<?php

namespace App\Events\Omnichannel;

use App\Events\Omnichannel\Concerns\BroadcastsOmnichannelEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationResolved implements ShouldBroadcastNow
{
    use BroadcastsOmnichannelEvent;
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $conversationId,
        public readonly ?string $status = 'resolved',
    ) {}

    public function broadcastAs(): string
    {
        return 'ConversationResolved';
    }
}
