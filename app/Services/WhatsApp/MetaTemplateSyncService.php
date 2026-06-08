<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaTemplateSyncService
{
    /**
     * @return array{synced:int, templates:\Illuminate\Support\Collection<int, WhatsAppMessageTemplate>}
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
        $syncedAt = now();

        $response = Http::withToken((string) $provider->api_token)
            ->timeout(15)
            ->get("{$baseUrl}/{$version}/{$wabaId}/message_templates");

        if (! $response->successful()) {
            $message = data_get($response->json() ?? [], 'error.message')
                ?? "Meta template sync failed with HTTP {$response->status()}.";

            throw new RuntimeException((string) $message);
        }

        $templates = collect($response->json('data') ?? [])
            ->filter(fn ($template) => is_array($template))
            ->map(function (array $template) use ($provider, $syncedAt) {
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
            });

        return [
            'synced' => $templates->count(),
            'templates' => $templates,
        ];
    }
}
