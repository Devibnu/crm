<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppService implements WhatsAppServiceInterface
{
    public function __construct(private readonly WhatsAppProvider $provider)
    {
    }

    public function sendMessage(string $phone, string $message, array $options = []): array
    {
        if (($options['type'] ?? null) === 'template') {
            $templateName = trim((string) ($options['template_name'] ?? $this->provider->meta_template_name ?? ''));
            $languageCode = trim((string) ($options['language_code'] ?? $this->provider->meta_template_language ?? ''));

            return $this->sendTemplateMessage(
                $phone,
                $templateName,
                $languageCode,
                $options,
            );
        }

        return $this->sendPayload($phone, [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhoneNumber($phone),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ], $options);
    }

    public function sendTemplateMessage(string $phone, string $templateName = '', string $languageCode = '', array $options = []): array
    {
        $templateName = trim($templateName) ?: trim((string) $this->provider->meta_template_name);
        $languageCode = trim($languageCode) ?: trim((string) $this->provider->meta_template_language) ?: 'id';

        if ($templateName === '') {
            return [
                'success' => false,
                'provider' => 'meta',
                'message_id' => null,
                'delivery_status' => 'failed',
                'message_type' => 'template',
                'template_name' => null,
                'raw' => [
                    'error' => 'Meta template name is not configured. Use an approved template from WhatsApp Manager.',
                ],
                'reason' => 'Meta template name is not configured. Use an approved template from WhatsApp Manager.',
            ];
        }

        $template = [
            'name' => $templateName,
            'language' => [
                'code' => $languageCode,
            ],
        ];

        $components = $options['components'] ?? [];

        if (is_array($components) && $components !== []) {
            $template['components'] = $components;
        }

        return $this->sendPayload($phone, [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhoneNumber($phone),
            'type' => 'template',
            'template' => $template,
        ], $options + [
            'template_name' => $templateName,
            'language_code' => $languageCode,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function sendPayload(string $phone, array $payload, array $options = []): array
    {
        $baseUrl = rtrim((string) ($this->provider->api_url ?: 'https://graph.facebook.com'), '/');
        $version = trim((string) ($this->provider->graph_api_version ?: 'v23.0'), '/');
        $phoneNumberId = trim((string) $this->provider->device_id);
        $apiToken = (string) $this->provider->api_token;
        $endpoint = "{$baseUrl}/{$version}/{$phoneNumberId}/messages";
        $safeContext = [
            'provider_id' => $this->provider->id,
            'phone_number_id' => $phoneNumberId,
            'device_id' => $phoneNumberId,
            'graph_api_version' => $version,
            'endpoint_url' => $endpoint,
            'token_is_encrypted_like' => str_starts_with($apiToken, 'eyJpdiI6'),
            'token_prefix' => substr($apiToken, 0, 12),
            'token_length' => strlen($apiToken),
            'payload_template_name' => data_get($payload, 'template.name'),
            'payload_language' => data_get($payload, 'template.language.code'),
        ];

        Log::info('Meta WhatsApp send payload prepared', $safeContext + [
            'provider_name' => $this->provider->name,
            'phone_number' => $phone,
            'message_type' => $payload['type'] ?? null,
        ]);

        try {
            $response = Http::withToken($apiToken)
                ->asJson()
                ->timeout((int) ($options['timeout'] ?? 10))
                ->retry((int) ($options['retry_times'] ?? 2), (int) ($options['retry_sleep'] ?? 200), throw: false)
                ->post($endpoint, $payload);

            $json = $response->json();
            $raw = is_array($json) ? $json : ['response' => $json];
            $messageId = data_get($raw, 'messages.0.id');
            $success = $response->successful() && $messageId !== null;

            Log::info('Meta WhatsApp send payload response', $safeContext + [
                'phone_number' => $phone,
                'success' => $success,
                'message_id' => $messageId,
                'response_status' => $response->status(),
                'response_json' => $raw,
                'has_error' => isset($raw['error']),
            ]);

            return [
                'success' => $success,
                'provider' => 'meta',
                'message_id' => $messageId,
                'delivery_status' => $success ? 'accepted' : 'failed',
                'message_type' => $payload['type'] ?? null,
                'template_name' => $options['template_name'] ?? null,
                'raw' => $raw,
                'reason' => $success
                    ? 'Accepted by Meta. Delivered/read status will arrive via webhook.'
                    : $this->failureReason($raw, $response->status()),
            ];
        } catch (ConnectionException $exception) {
            Log::error('Meta WhatsApp connection failed', $safeContext + [
                'phone_number' => $phone,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'provider' => 'meta',
                'message_id' => null,
                'delivery_status' => 'failed',
                'message_type' => $payload['type'] ?? null,
                'template_name' => $options['template_name'] ?? null,
                'raw' => [
                    'error' => $exception->getMessage(),
                ],
                'reason' => $exception->getMessage(),
            ];
        }
    }

    public function sendBroadcast(array $recipients, array $payload): array
    {
        return [
            'success' => false,
            'provider' => 'meta',
            'message_id' => null,
            'raw' => [
                'reason' => 'Broadcast queue integration is not implemented yet.',
                'recipient_count' => count($recipients),
            ],
            'reason' => 'Broadcast queue integration is not implemented yet.',
        ];
    }

    public function validateWebhook(array $payload): array
    {
        return [
            'success' => true,
            'provider' => 'meta',
            'message_id' => null,
            'raw' => [
                'valid' => true,
                'payload_keys' => array_keys($payload),
            ],
        ];
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function failureReason(array $raw, int $status): string
    {
        $message = data_get($raw, 'error.message')
            ?? data_get($raw, 'error.error_data.details')
            ?? data_get($raw, 'message')
            ?? "Meta Cloud API request failed with HTTP {$status}.";

        $parts = [(string) $message];
        $code = data_get($raw, 'error.code');
        $type = data_get($raw, 'error.type');
        $details = data_get($raw, 'error.error_data.details');

        if ($code !== null && $code !== '') {
            $parts[] = "Code: {$code}";
        }

        if ($type !== null && $type !== '') {
            $parts[] = "Type: {$type}";
        }

        if ($details !== null && $details !== '' && $details !== $message) {
            $parts[] = "Details: {$details}";
        }

        $parts[] = "HTTP: {$status}";

        return implode(' | ', $parts);
    }
}
