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
        if ($provider->provider !== 'meta') {
            throw new RuntimeException('Template sync hanya tersedia untuk provider Meta.');
        }

        $wabaId = trim((string) $provider->business_account_id);

        if ($wabaId === '') {
            throw new RuntimeException('WABA ID belum dikonfigurasi.');
        }

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
                $message = data_get($response->json() ?? [], 'error.message')
                    ?? data_get($response->json() ?? [], 'error.error_data.details')
                    ?? "Meta template sync failed with HTTP {$response->status()}.";
                $code = data_get($response->json() ?? [], 'error.code');
                $type = data_get($response->json() ?? [], 'error.type');
                $context = trim("Provider: {$provider->name}. WABA ID: {$wabaId}. Endpoint: {$endpoint}. Error code: {$code}. Type: {$type}.");

                throw new RuntimeException(trim((string) $message).' '.$context);
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
                        'header' => is_array($header) ? ($header['text'] ?? ($header['format'] ?? null)) : null,
                        'footer' => is_array($footer) ? ($footer['text'] ?? null) : null,
                        'buttons' => is_array($buttons) ? ($buttons['buttons'] ?? null) : null,
                        'raw' => $template,
                        'last_synced_at' => $syncedAt,
                    ],
                );
            })
            ->filter();

        return [
            'synced' => $templates->count(),
            'templates' => $templates,
            'endpoint' => $endpoint,
            'waba_id' => $wabaId,
        ];
    }
}
