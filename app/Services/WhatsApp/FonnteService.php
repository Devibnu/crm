<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class FonnteService implements WhatsAppServiceInterface
{
    public function __construct(private readonly WhatsAppProvider $provider)
    {
    }

    public function sendMessage(string $phone, string $message, array $options = []): array
    {
        $baseUrl = rtrim((string) $this->provider->api_url, '/');

        try {
            $response = Http::withHeaders([
                'Authorization' => (string) $this->provider->api_token,
            ])
                ->timeout((int) ($options['timeout'] ?? 10))
                ->retry((int) ($options['retry_times'] ?? 2), (int) ($options['retry_sleep'] ?? 200), throw: false)
                ->post($baseUrl . '/send', [
                    'target' => $phone,
                    'message' => $message,
                ]);

            $raw = $response->json() ?? [];
            $success = $response->successful() && (bool) ($raw['status'] ?? false);

            return [
                'success' => $success,
                'provider' => 'fonnte',
                'message_id' => $raw['id'] ?? null,
                'raw' => $raw,
            ];
        } catch (ConnectionException $exception) {
            return [
                'success' => false,
                'provider' => 'fonnte',
                'message_id' => null,
                'raw' => [
                    'error' => $exception->getMessage(),
                ],
            ];
        }
    }

    public function sendBroadcast(array $recipients, array $payload): array
    {
        return [
            'success' => false,
            'provider' => 'fonnte',
            'message_id' => null,
            'raw' => [
                'reason' => 'Broadcast queue integration is not implemented yet.',
                'recipient_count' => count($recipients),
            ],
        ];
    }

    public function validateWebhook(array $payload): array
    {
        return [
            'success' => true,
            'provider' => 'fonnte',
            'message_id' => null,
            'raw' => [
                'valid' => true,
                'payload_keys' => array_keys($payload),
            ],
        ];
    }
}
