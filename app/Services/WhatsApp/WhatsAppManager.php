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
        $template = $this->resolveTemplate($provider, $templateName, $languageCode);

        return $this->driver()->sendMessage($phone, '', $options + [
            'type' => 'template',
            'template_name' => $template['name'],
            'language_code' => $template['language'],
        ]);
    }

    public function sendBroadcast(array $recipients, array $payload): array
    {
        return $this->driver()->sendBroadcast($recipients, $payload);
    }

    /**
     * @return array{name:?string, language:?string}
     */
    public function resolveTemplate(WhatsAppProvider $provider, ?string $templateName = null, ?string $languageCode = null): array
    {
        if ($templateName !== null && trim($templateName) !== '') {
            return [
                'name' => $templateName,
                'language' => $languageCode ?: $provider->meta_template_language,
            ];
        }

        $defaultTemplate = $provider->messageTemplates()
            ->where('status', 'APPROVED')
            ->where('is_default', true)
            ->first();

        $template = $defaultTemplate
            ?? $provider->messageTemplates()
                ->where('status', 'APPROVED')
                ->oldest()
                ->first();

        if ($template !== null) {
            if (! $template->is_default) {
                $provider->messageTemplates()
                    ->whereKeyNot($template->id)
                    ->update(['is_default' => false]);
                $template->update(['is_default' => true]);
            }

            if ($provider->meta_template_name !== $template->name || $provider->meta_template_language !== $template->language) {
                $provider->update([
                    'meta_template_name' => $template->name,
                    'meta_template_language' => $template->language,
                ]);
            }

            return [
                'name' => $template->name,
                'language' => $template->language,
            ];
        }

        return [
            'name' => $provider->meta_template_name,
            'language' => $provider->meta_template_language,
        ];
    }
}
