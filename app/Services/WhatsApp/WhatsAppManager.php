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

    public function sendBroadcast(array $recipients, array $payload): array
    {
        return $this->driver()->sendBroadcast($recipients, $payload);
    }
}
