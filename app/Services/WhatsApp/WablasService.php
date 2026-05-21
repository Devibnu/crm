<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppProvider;

class WablasService implements WhatsAppServiceInterface
{
    public function __construct(private readonly WhatsAppProvider $provider)
    {
    }

    public function sendMessage(string $phone, string $message, array $options = []): array
    {
        return $this->notImplemented('send_message', [
            'target' => $phone,
            'message' => $message,
        ]);
    }

    public function sendBroadcast(array $recipients, array $payload): array
    {
        return $this->notImplemented('send_broadcast', [
            'recipient_count' => count($recipients),
        ]);
    }

    public function validateWebhook(array $payload): array
    {
        return $this->notImplemented('validate_webhook', [
            'payload_keys' => array_keys($payload),
        ]);
    }

    private function notImplemented(string $action, array $context = []): array
    {
        return [
            'success' => false,
            'provider' => 'wablas',
            'provider_id' => $this->provider->id,
            'action' => $action,
            'message_id' => null,
            'raw' => [
                'reason' => 'Wablas integration is not implemented yet.',
                'context' => $context,
            ],
        ];
    }
}
