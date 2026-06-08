<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaTemplateSyncService
{
    /**
     * @return array{synced:int, templates:\Illuminate\Support\Collection<int, WhatsAppMessageTemplate>, endpoint:string, waba_id:string}
     */
    public function sync(WhatsAppProvider $provider): array
    {
        $this->assertMetaProvider($provider);
        $this->refreshConnection($provider);

        $wabaId = trim((string) $provider->business_account_id);
        $baseUrl = rtrim((string) ($provider->api_url ?: 'https://graph.facebook.com'), '/');
        $version = trim((string) ($provider->graph_api_version ?: 'v23.0'), '/');
        $endpoint = "{$baseUrl}/{$version}/{$wabaId}/message_templates";
        $syncedAt = now();

        $templatesPayload = [];
        $url = $endpoint;
        $query = [
            'fields' => 'id,name,category,language,status,components,quality_score,rejected_reason',
            'limit' => 100,
        ];

        do {
            $response = Http::withToken((string) $provider->api_token)
                ->timeout(20)
                ->get($url, $query);

            if (! $response->successful()) {
                $this->markFailed($provider, $response->json() ?? [], $response->status(), $endpoint);
            }

            $pageData = $response->json('data') ?? [];

            if (is_array($pageData)) {
                array_push($templatesPayload, ...array_filter($pageData, fn ($template) => is_array($template)));
            }

            $url = (string) data_get($response->json() ?? [], 'paging.next', '');
            $query = [];
        } while ($url !== '');

        if ($templatesPayload === []) {
            throw new RuntimeException("Meta mengembalikan 0 template. Provider: {$provider->name}. WABA ID: {$wabaId}. Endpoint: {$endpoint}. Pastikan token punya akses ke WABA ini dan template berada di WhatsApp Business Account yang sama.");
        }

        $templates = collect($templatesPayload)
            ->filter(fn ($template) => is_array($template))
            ->map(function (array $template) use ($provider, $syncedAt) {
                $name = trim((string) ($template['name'] ?? ''));
                $language = trim((string) ($template['language'] ?? ''));

                if ($name === '' || $language === '') {
                    return null;
                }

                $components = collect($template['components'] ?? [])->filter(fn ($component) => is_array($component));

                $header = $components->firstWhere('type', 'HEADER');
                $body = $components->firstWhere('type', 'BODY');
                $footer = $components->firstWhere('type', 'FOOTER');
                $buttons = $components->firstWhere('type', 'BUTTONS');

                return WhatsAppMessageTemplate::query()->updateOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'name' => (string) ($template['name'] ?? ''),
                        'language' => (string) ($template['language'] ?? ''),
                    ],
                    [
                        'template_id' => $template['id'] ?? null,
                        'category' => $template['category'] ?? null,
                        'status' => $template['status'] ?? null,
                        'body' => is_array($body) ? ($body['text'] ?? null) : null,
                        'body_meta' => is_array($body) ? ($body['text'] ?? null) : null,
                        'header' => is_array($header) ? ($header['text'] ?? ($header['format'] ?? null)) : null,
                        'footer' => is_array($footer) ? ($footer['text'] ?? null) : null,
                        'buttons' => is_array($buttons) ? ($buttons['buttons'] ?? null) : null,
                        'source' => 'meta_sync',
                        'approved_at' => ($template['status'] ?? null) === 'APPROVED' ? now() : null,
                        'rejected_reason' => $template['rejected_reason'] ?? null,
                        'raw' => $template,
                        'last_synced_at' => $syncedAt,
                    ],
                );
            })
            ->filter();

        $this->ensureDefaultTemplate($provider);

        return [
            'synced' => $templates->count(),
            'templates' => $templates,
            'endpoint' => $endpoint,
            'waba_id' => $wabaId,
        ];
    }

    /**
     * @return array{display_phone_number:?string, verified_name:?string, endpoint:string}
     */
    public function refreshConnection(WhatsAppProvider $provider): array
    {
        $this->assertMetaProvider($provider);

        $phoneNumberId = trim((string) $provider->device_id);

        if ($phoneNumberId === '') {
            throw new RuntimeException('Phone Number ID belum dikonfigurasi.');
        }

        $baseUrl = rtrim((string) ($provider->api_url ?: 'https://graph.facebook.com'), '/');
        $version = trim((string) ($provider->graph_api_version ?: 'v23.0'), '/');
        $endpoint = "{$baseUrl}/{$version}/{$phoneNumberId}";
        $response = Http::withToken((string) $provider->api_token)
            ->timeout(20)
            ->get($endpoint, [
                'fields' => 'display_phone_number,verified_name',
            ]);

        if (! $response->successful()) {
            $this->markFailed($provider, $response->json() ?? [], $response->status(), $endpoint);
        }

        $provider->update([
            'display_phone_number' => $response->json('display_phone_number'),
            'verified_name' => $response->json('verified_name'),
            'meta_connection_status' => 'connected',
            'meta_connection_error' => null,
            'last_connected_at' => now(),
        ]);

        return [
            'display_phone_number' => $provider->fresh()->display_phone_number,
            'verified_name' => $provider->fresh()->verified_name,
            'endpoint' => $endpoint,
        ];
    }

    private function assertMetaProvider(WhatsAppProvider $provider): void
    {
        if ($provider->provider !== 'meta') {
            throw new RuntimeException('Template sync hanya tersedia untuk provider Meta.');
        }

        if (trim((string) $provider->business_account_id) === '') {
            throw new RuntimeException('WABA ID belum dikonfigurasi.');
        }
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function markFailed(WhatsAppProvider $provider, array $raw, int $httpStatus, string $endpoint): never
    {
        $classification = $this->classifyError($raw, $httpStatus);
        $message = $classification === 'token_expired'
            ? 'Token Meta telah kedaluwarsa. Silakan perbarui Access Token pada System -> WhatsApp Providers.'
            : ($classification === 'token_invalid'
                ? 'Token Meta tidak valid. Silakan periksa Access Token pada System -> WhatsApp Providers.'
                : (string) (data_get($raw, 'error.message')
                    ?? data_get($raw, 'error.error_data.details')
                    ?? "Meta Graph API gagal dengan HTTP {$httpStatus}."));

        $provider->update([
            'meta_connection_status' => $classification,
            'meta_connection_error' => $message,
        ]);

        $code = data_get($raw, 'error.code');
        $subcode = data_get($raw, 'error.error_subcode');
        $type = data_get($raw, 'error.type');
        $context = trim("Provider: {$provider->name}. Endpoint: {$endpoint}. Error code: {$code}. Subcode: {$subcode}. Type: {$type}.");

        throw new RuntimeException(trim($message.' '.$context));
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function classifyError(array $raw, int $httpStatus): string
    {
        $code = (string) data_get($raw, 'error.code', '');
        $subcode = (string) data_get($raw, 'error.error_subcode', '');
        $message = mb_strtolower((string) data_get($raw, 'error.message', ''));

        if ($code === '190' && in_array($subcode, ['463', '467', '460'], true)) {
            return 'token_expired';
        }

        if ($code === '190' && str_contains($message, 'expired')) {
            return 'token_expired';
        }

        if ($code === '190' || $httpStatus === 401) {
            return 'token_invalid';
        }

        return 'connection_error';
    }

    private function ensureDefaultTemplate(WhatsAppProvider $provider): void
    {
        $approvedTemplates = $provider->messageTemplates()
            ->where('status', 'APPROVED')
            ->oldest()
            ->get();

        if ($approvedTemplates->isEmpty()) {
            return;
        }

        $defaultTemplate = $approvedTemplates->firstWhere('is_default', true);

        if ($defaultTemplate === null && $approvedTemplates->count() === 1) {
            $defaultTemplate = $approvedTemplates->first();
        }

        if ($defaultTemplate === null && trim((string) $provider->meta_template_name) === '') {
            $defaultTemplate = $approvedTemplates->first();
        }

        if ($defaultTemplate === null) {
            return;
        }

        $provider->messageTemplates()
            ->whereKeyNot($defaultTemplate->id)
            ->update(['is_default' => false]);

        $defaultTemplate->update(['is_default' => true]);
        $provider->update([
            'meta_template_name' => $defaultTemplate->name,
            'meta_template_language' => $defaultTemplate->language,
        ]);
    }
}
