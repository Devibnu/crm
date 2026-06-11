<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
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

    public function sendMediaMessage(string $phone, string $filePath, string $mediaType, array $options = []): array
    {
        $mediaType = $this->normalizeMediaType($mediaType);
        $context = $this->metaContext($phone);
        $mediaEndpoint = $this->endpointUrl('media');

        try {
            $fileHandle = fopen($filePath, 'r');

            if ($fileHandle === false) {
                return $this->mediaFailureResult($mediaType, [
                    'error' => 'Unable to open media file for upload.',
                    'file_path' => $filePath,
                ]);
            }

            $uploadResponse = $this->httpClient($options)
                ->attach('file', $fileHandle, (string) ($options['filename'] ?? basename($filePath)))
                ->post($mediaEndpoint, [
                    'messaging_product' => 'whatsapp',
                    'type' => (string) ($options['mime_type'] ?? 'application/octet-stream'),
                ]);

            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }

            $uploadJson = $uploadResponse->json();
            $uploadRaw = is_array($uploadJson) ? $uploadJson : ['response' => $uploadJson];
            $mediaId = data_get($uploadRaw, 'id');

            if (! $uploadResponse->successful() || ! is_string($mediaId) || $mediaId === '') {
                return $this->mediaFailureResult($mediaType, [
                    'upload' => $uploadRaw,
                    'upload_status' => $uploadResponse->status(),
                ], $this->failureReason($uploadRaw, $uploadResponse->status()));
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalizePhoneNumber($phone),
                'type' => $mediaType,
                $mediaType => $this->mediaPayload($mediaType, $mediaId, $options),
            ];
            $sendResult = $this->sendPayload($phone, $payload, $options);

            $sendResult['media_id'] = $mediaId;
            $sendResult['message_type'] = $mediaType;
            $sendResult['raw'] = [
                'upload' => $uploadRaw,
                'message' => $sendResult['raw'] ?? [],
            ];

            return $sendResult;
        } catch (ConnectionException $exception) {
            Log::error('Meta WhatsApp media send connection failed', $context + [
                'phone_number' => $phone,
                'media_type' => $mediaType,
                'error' => $exception->getMessage(),
            ]);

            return $this->mediaFailureResult($mediaType, [
                'error' => $exception->getMessage(),
            ], $exception->getMessage());
        }
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
        $endpoint = $this->endpointUrl('messages');
        $safeContext = $this->metaContext($phone) + [
            'endpoint_url' => $endpoint,
            'payload_template_name' => data_get($payload, 'template.name'),
            'payload_language' => data_get($payload, 'template.language.code'),
        ];

        Log::info('Meta WhatsApp send payload prepared', $safeContext + [
            'provider_name' => $this->provider->name,
            'phone_number' => $phone,
            'message_type' => $payload['type'] ?? null,
        ]);

        try {
            $response = $this->httpClient($options)
                ->asJson()
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

    private function normalizeMediaType(string $mediaType): string
    {
        return in_array($mediaType, ['image', 'document', 'video', 'audio'], true) ? $mediaType : 'document';
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function mediaPayload(string $mediaType, string $mediaId, array $options): array
    {
        $payload = ['id' => $mediaId];
        $caption = trim((string) ($options['caption'] ?? ''));

        if ($caption !== '' && in_array($mediaType, ['image', 'document', 'video'], true)) {
            $payload['caption'] = $caption;
        }

        if ($mediaType === 'document' && trim((string) ($options['filename'] ?? '')) !== '') {
            $payload['filename'] = trim((string) $options['filename']);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function httpClient(array $options): PendingRequest
    {
        return Http::withToken((string) $this->provider->api_token)
            ->timeout((int) ($options['timeout'] ?? 10))
            ->retry((int) ($options['retry_times'] ?? 2), (int) ($options['retry_sleep'] ?? 200), throw: false);
    }

    private function endpointUrl(string $resource): string
    {
        $baseUrl = rtrim((string) ($this->provider->api_url ?: 'https://graph.facebook.com'), '/');
        $version = trim((string) ($this->provider->graph_api_version ?: 'v23.0'), '/');
        $phoneNumberId = trim((string) $this->provider->device_id);

        return "{$baseUrl}/{$version}/{$phoneNumberId}/{$resource}";
    }

    /**
     * @return array<string, mixed>
     */
    private function metaContext(string $phone): array
    {
        $phoneNumberId = trim((string) $this->provider->device_id);
        $apiToken = (string) $this->provider->api_token;

        return [
            'provider_id' => $this->provider->id,
            'phone_number_id' => $phoneNumberId,
            'device_id' => $phoneNumberId,
            'graph_api_version' => trim((string) ($this->provider->graph_api_version ?: 'v23.0'), '/'),
            'token_is_encrypted_like' => str_starts_with($apiToken, 'eyJpdiI6'),
            'token_prefix' => substr($apiToken, 0, 12),
            'token_length' => strlen($apiToken),
            'phone_number' => $phone,
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function mediaFailureResult(string $mediaType, array $raw, ?string $reason = null): array
    {
        return [
            'success' => false,
            'provider' => 'meta',
            'message_id' => null,
            'media_id' => null,
            'delivery_status' => 'failed',
            'message_type' => $mediaType,
            'raw' => $raw,
            'reason' => $reason ?? (string) data_get($raw, 'error', 'Meta Cloud API media request failed.'),
        ];
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
