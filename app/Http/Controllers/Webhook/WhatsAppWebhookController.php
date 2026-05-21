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
}
