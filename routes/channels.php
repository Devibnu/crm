<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('omnichannel', fn ($user): bool => $user?->can('omnichannel.view') ?? false);

Broadcast::channel('omnichannel.conversation.{conversationId}', fn ($user, int $conversationId): bool => $user?->can('omnichannel.view') ?? false);
