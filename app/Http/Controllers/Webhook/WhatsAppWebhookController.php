<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppWebhookController
{
    public function handleFonnte(Request $request, FonnteWebhookController $controller): JsonResponse
    {
        return $controller($request);
    }

    public function verifyMeta(Request $request, MetaWebhookController $controller)
    {
        return $controller->verify($request);
    }

    public function handleMeta(Request $request, MetaWebhookController $controller): JsonResponse
    {
        return $controller->handle($request);
    }
}
