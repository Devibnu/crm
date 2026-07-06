<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\WhatsApp\WhatsAppConversationService;

class WhatsAppWebhookController
{
    public function handleFonnte(Request $request, FonnteWebhookController $controller, WhatsAppConversationService $conversationService): JsonResponse
    {
        return $controller($request, $conversationService);
    }

    public function verifyMeta(Request $request, MetaWebhookController $controller)
    {
        return $controller->verify($request);
    }

    public function handleMeta(Request $request, MetaWebhookController $controller, WhatsAppConversationService $conversationService): JsonResponse
    {
        return $controller->handle($request, $conversationService);
    }
}
