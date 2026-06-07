<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppProvider;
use RuntimeException;

class WhatsAppManager
{
    public function provider(): WhatsAppProvider
    {
        $provider = WhatsAppProvider::query()->default()->active()->first();

        if ($provider === null) {
            throw new RuntimeException('No active WhatsApp provider configured.');
        }

        return $provider;
    }

    public function driver(?WhatsAppProvider $provider = null): WhatsAppServiceInterface
    {
        $provider ??= $this->provider();

        return match ($provider->provider) {
            'fonnte' => new FonnteService($provider),
            'wablas' => new WablasService($provider),
            'meta' => new MetaWhatsAppService($provider),
            default => throw new RuntimeException("Unsupported WhatsApp provider [{$provider->provider}]."),
        };
    }

    public function sendMessage(string $phone, string $message, array $options = []): array
    {
        return $this->driver()->sendMessage($phone, $message, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function sendTemplateMessage(string $phone, ?string $templateName = null, ?string $languageCode = null, array $options = []): array
    {
        $provider = $this->provider();

        return $this->driver()->sendMessage($phone, '', $options + [
            'type' => 'template',
            'template_name' => $templateName ?? $provider->meta_template_name,
            'language_code' => $languageCode ?? $provider->meta_template_language,
        ]);
    }

    public function sendBroadcast(array $recipients, array $payload): array
    {
        return $this->driver()->sendBroadcast($recipients, $payload);
    }
}
