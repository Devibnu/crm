<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageTemplate;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $broadcastId,
        public readonly int $recipientId,
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(WhatsAppManager $manager): void
    {
        Log::info('Processing recipient', [
            'broadcast_id' => $this->broadcastId,
            'recipient_id' => $this->recipientId,
            'status' => 'start',
        ]);

        Log::info('Processing WhatsApp broadcast recipient', [
            'broadcast_id' => $this->broadcastId,
            'recipient_id' => $this->recipientId,
        ]);

        $broadcast = WhatsAppBroadcast::query()->find($this->broadcastId);
        $recipient = WhatsAppBroadcastRecipient::query()->find($this->recipientId);

        if ($broadcast === null || $recipient === null) {
            Log::warning('Skipping WhatsApp broadcast recipient', [
                'broadcast_id' => $this->broadcastId,
                'recipient_id' => $this->recipientId,
                'reason' => 'broadcast_or_recipient_missing',
            ]);

            return;
        }

        Log::info('WhatsApp broadcast recipient loaded', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'broadcast_status' => $broadcast->status,
            'recipient_status' => $recipient->status,
            'recipient_phone' => $recipient->phone_number,
        ]);

        if ($broadcast->status === 'paused') {
            $recipient->update(['status' => 'queued']);
            Log::info('Skipping WhatsApp broadcast recipient', [
                'broadcast_id' => $broadcast->id,
                'recipient_id' => $recipient->id,
                'reason' => 'broadcast_paused',
                'broadcast_status' => $broadcast->status,
                'recipient_status' => $recipient->status,
            ]);

            return;
        }

        if (! in_array($broadcast->status, ['sending', 'scheduled'], true)) {
            Log::info('Skipping WhatsApp broadcast recipient', [
                'broadcast_id' => $broadcast->id,
                'recipient_id' => $recipient->id,
                'reason' => 'broadcast_status_not_sendable',
                'broadcast_status' => $broadcast->status,
                'recipient_status' => $recipient->status,
            ]);

            return;
        }

        if (! in_array($recipient->status, ['queued', 'sending'], true)) {
            Log::info('Skipping WhatsApp broadcast recipient', [
                'broadcast_id' => $broadcast->id,
                'recipient_id' => $recipient->id,
                'reason' => 'recipient_status_not_sendable',
                'broadcast_status' => $broadcast->status,
                'recipient_status' => $recipient->status,
            ]);

            return;
        }

        $phone = $this->normalizePhoneNumber($recipient->phone_number);
        Log::info('WhatsApp broadcast recipient phone normalized', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'broadcast_status' => $broadcast->status,
            'recipient_status' => $recipient->status,
            'normalized_phone' => $phone,
        ]);

        if ($phone === null) {
            $this->markFailed($broadcast, $recipient, 'Invalid WhatsApp recipient phone number.');

            return;
        }

        $recipient->update([
            'status' => 'sending',
            'phone_number' => $phone,
            'error_message' => null,
            'failed_reason' => null,
        ]);
        $recipient->phone_number = $phone;
        $recipient->status = 'sending';

        Log::info('WhatsApp broadcast recipient status updated to sending', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'broadcast_status' => $broadcast->status,
            'recipient_status' => 'sending',
            'normalized_phone' => $phone,
        ]);

        $provider = $manager->provider();
        $message = $this->personalizedMessage($broadcast, $recipient);
        $usesMetaTemplate = $provider->provider === 'meta'
            && ($broadcast->send_mode === 'meta_template' || ! $this->hasOpenMetaCustomerServiceWindow($phone));

        $result = $usesMetaTemplate
            ? $manager->sendTemplateMessage(
                $phone,
                $broadcast->messageTemplate?->name,
                $broadcast->messageTemplate?->language,
                ['components' => $this->templateComponents($broadcast, $recipient)]
            )
            : $manager->sendMessage($phone, $message);

        Log::info('WhatsApp broadcast provider result received', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'broadcast_status' => $broadcast->status,
            'recipient_status' => $recipient->status,
            'normalized_phone' => $phone,
            'provider_success' => (bool) ($result['success'] ?? false),
            'provider_message_id' => $result['message_id'] ?? null,
                'provider' => $result['provider'] ?? $provider->provider,
                'message_type' => $result['message_type'] ?? ($usesMetaTemplate ? 'template' : 'text'),
                'template_name' => $result['template_name'] ?? null,
                'provider_error' => (bool) ($result['success'] ?? false) ? null : $this->errorMessageFromResult($result),
            ]);

        if (! (bool) ($result['success'] ?? false)) {
            $this->markFailed($broadcast, $recipient, $this->errorMessageFromResult($result));

            return;
        }

        $recipient->update([
            'status' => 'sent',
            'provider_message_id' => $result['message_id'] ?? null,
            'sent_at' => now(),
            'error_message' => null,
            'failed_reason' => null,
        ]);
        $this->recordOutboundMessage($broadcast, $recipient, $phone, $message, $result, $usesMetaTemplate);

        $broadcast->refreshDeliveryStats();
        $this->completeBroadcastIfFinished($broadcast->fresh());

        Log::info('WhatsApp broadcast recipient marked sent', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'broadcast_status' => $broadcast->fresh()?->status,
            'recipient_status' => 'sent',
            'normalized_phone' => $phone,
            'provider_message_id' => $result['message_id'] ?? null,
            'delivery_status' => $result['delivery_status'] ?? null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $recipient = WhatsAppBroadcastRecipient::query()->find($this->recipientId);
        $broadcast = WhatsAppBroadcast::query()->find($this->broadcastId);

        if ($recipient === null || $broadcast === null) {
            return;
        }

        $message = $exception?->getMessage() ?: 'WhatsApp provider failed.';

        $this->markFailed($broadcast, $recipient, $message);
    }

    protected function personalizedMessage(WhatsAppBroadcast $broadcast, WhatsAppBroadcastRecipient $recipient): string
    {
        return strtr($broadcast->message_template, [
            '@{{name}}' => $recipient->recipient_name,
            '@{{nama}}' => $recipient->recipient_name,
            '{{name}}' => $recipient->recipient_name,
            '{{nama}}' => $recipient->recipient_name,
            '{name}' => $recipient->recipient_name,
            '{nama}' => $recipient->recipient_name,
            '@{{phone}}' => $recipient->phone_number,
            '{{phone}}' => $recipient->phone_number,
            '{phone}' => $recipient->phone_number,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function templateComponents(WhatsAppBroadcast $broadcast, WhatsAppBroadcastRecipient $recipient): array
    {
        $template = $broadcast->messageTemplate;

        if ($template === null) {
            return [];
        }

        $mapping = is_array($template->variable_mapping) ? $template->variable_mapping : [];
        $mapping = $this->normalizeTemplateMapping($mapping, (string) ($template->body_meta ?? $template->body ?? ''));

        if ($mapping === []) {
            return [];
        }

        $values = [];
        $maxIndex = max(array_map('intval', array_keys($mapping)));

        for ($index = 1; $index <= $maxIndex; $index++) {
            $key = (string) $index;
            $parameterName = $mapping[$key] ?? null;
            $values[] = $parameterName !== null
                ? $this->variableValue((string) $parameterName, $broadcast, $recipient)
                : '';
        }

        Log::info('WhatsApp broadcast template components built', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_parameter_count' => count($values),
            'template_mapping' => $mapping,
        ]);

        return [
            [
                'type' => 'body',
                'parameters' => array_map(fn ($value) => [
                    'type' => 'text',
                    'text' => (string) $value,
                ], $values),
            ],
        ];
    }

    /**
     * @param array<string, string> $mapping
     * @param string $bodyMeta
     * @return array<string, string>
     */
    protected function normalizeTemplateMapping(array $mapping, string $bodyMeta): array
    {
        $numericMapping = [];

        foreach ($mapping as $key => $value) {
            if (is_numeric($key) && trim((string) $value) !== '') {
                $numericMapping[(int) $key] = (string) $value;
            }
        }

        if ($numericMapping !== []) {
            ksort($numericMapping);

            return array_combine(array_map('strval', array_keys($numericMapping)), array_values($numericMapping));
        }

        return $this->inferMetaTemplateMapping($bodyMeta);
    }

    protected function inferMetaTemplateMapping(string $bodyMeta): array
    {
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $bodyMeta, $matches);

        $indexes = array_unique(array_map('intval', $matches[1] ?? []));
        sort($indexes);

        $mapping = [];

        foreach ($indexes as $index) {
            $mapping[(string) $index] = 'param_'.$index;
        }

        return $mapping;
    }

    protected function variableValue(string $name, WhatsAppBroadcast $broadcast, WhatsAppBroadcastRecipient $recipient): string
    {
        $contact = null;

        if ($recipient->recipient_type === 'customer') {
            $contact = Customer::query()->find($recipient->recipient_id);
        } elseif ($recipient->recipient_type === 'lead') {
            $contact = Lead::query()->find($recipient->recipient_id);
        }

        $defaults = is_array($broadcast->template_variable_defaults) ? $broadcast->template_variable_defaults : [];

        return match ($name) {
            'nama' => (string) ($recipient->recipient_name ?: $contact?->name ?: 'Customer'),
            'no_hp' => (string) ($recipient->phone_number ?: $contact?->phone ?: ''),
            'email' => (string) ($contact?->email ?? $defaults['email'] ?? 'user@example.com'),
            'tanggal' => (string) ($defaults['tanggal'] ?? now()->format('d M Y')),
            'kode' => (string) ($defaults['kode'] ?? 'ABC123'),
            'otp' => (string) ($defaults['otp'] ?? '123456'),
            'no_order' => (string) ($defaults['no_order'] ?? 'INV-001'),
            default => (string) ($defaults[$name] ?? ucfirst(str_replace('_', ' ', $name))),
        };
    }

    protected function normalizePhoneNumber(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62') || strlen($digits) < 10) {
            return null;
        }

        return $digits;
    }

    protected function markFailed(WhatsAppBroadcast $broadcast, WhatsAppBroadcastRecipient $recipient, string $message): void
    {
        $recipient->update([
            'status' => 'failed',
            'error_message' => $message,
            'failed_reason' => $message,
        ]);

        $broadcast->refreshDeliveryStats();
        $this->completeBroadcastIfFinished($broadcast->fresh());

        Log::info('WhatsApp broadcast recipient marked failed', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'broadcast_status' => $broadcast->fresh()?->status,
            'recipient_status' => 'failed',
            'error_message' => $message,
        ]);
    }

    protected function hasOpenMetaCustomerServiceWindow(string $phone): bool
    {
        return WhatsAppMessage::query()
            ->where('provider', 'meta')
            ->where('direction', 'inbound')
            ->where('phone', $phone)
            ->where(function ($query) {
                $query
                    ->where('received_at', '>=', now()->subHours(24))
                    ->orWhere('sent_at', '>=', now()->subHours(24))
                    ->orWhere('created_at', '>=', now()->subHours(24));
            })
            ->exists();
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function recordOutboundMessage(
        WhatsAppBroadcast $broadcast,
        WhatsAppBroadcastRecipient $recipient,
        string $phone,
        string $message,
        array $result,
        bool $usesMetaTemplate,
    ): void {
        $conversation = WhatsAppConversation::query()->updateOrCreate(
            ['phone_number' => $phone],
            [
                'customer_id' => $recipient->recipient_type === 'customer' ? $recipient->recipient_id : null,
                'lead_id' => $recipient->recipient_type === 'lead' ? $recipient->recipient_id : null,
                'contact_name' => $recipient->recipient_name,
                'channel' => 'whatsapp',
                'last_message' => $usesMetaTemplate ? 'Template: '.($result['template_name'] ?? 'configured Meta template') : $message,
                'last_message_at' => now(),
                'status' => 'open',
            ],
        );

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $recipient->recipient_type === 'customer' ? $recipient->recipient_id : null,
            'lead_id' => $recipient->recipient_type === 'lead' ? $recipient->recipient_id : null,
            'phone' => $phone,
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => $usesMetaTemplate ? 'Template: '.($result['template_name'] ?? 'configured Meta template') : $message,
            'provider_message_id' => $result['message_id'] ?? null,
            'provider' => $result['provider'] ?? null,
            'broadcast_id' => $broadcast->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed>|object|string|null $result
     */
    protected function errorMessageFromResult(array|object|string|null $result): string
    {
        if ($result === null) {
            return 'WhatsApp provider failed.';
        }

        if (is_string($result)) {
            return $result !== '' ? $result : 'WhatsApp provider failed.';
        }

        if (is_object($result)) {
            $result = json_decode(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);
        }

        $candidate = $result['raw'] ?? $result;

        $message = $this->extractProviderErrorMessage($candidate);

        if (is_string($message) && $message !== '') {
            return $message;
        }

        if (is_numeric($message)) {
            return (string) $message;
        }

        if (is_array($message) || is_object($message)) {
            $encoded = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded !== false ? $encoded : 'WhatsApp provider failed.';
        }

        if ($candidate !== null) {
            $encoded = is_string($candidate)
                ? $candidate
                : json_encode($candidate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded ?: 'WhatsApp provider failed.';
        }

        return 'WhatsApp provider failed.';
    }

    /**
     * @param array<string, mixed>|object|string $payload
     * @return mixed
     */
    protected function extractProviderErrorMessage(array|object|string $payload): mixed
    {
        if (is_string($payload)) {
            return $payload;
        }

        if (is_object($payload)) {
            $payload = json_decode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);
        }

        $paths = [
            ['raw', 'error', 'message'],
            ['raw', 'message'],
            ['error', 'message'],
            ['reason'],
            ['message'],
        ];

        foreach ($paths as $path) {
            $value = $this->arrayGet($payload, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        if (isset($payload['error']) && ! is_scalar($payload['error'])) {
            return $payload['error'];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $array
     * @param array<int, string> $path
     * @return mixed
     */
    protected function arrayGet(array $array, array $path): mixed
    {
        $current = $array;

        foreach ($path as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    protected function completeBroadcastIfFinished(?WhatsAppBroadcast $broadcast): void
    {
        if ($broadcast === null || $broadcast->status !== 'sending') {
            return;
        }

        $remaining = $broadcast->recipients()
            ->whereIn('status', ['queued', 'sending'])
            ->count();

        if ($remaining > 0) {
            return;
        }

        $broadcast->update([
            'status' => $broadcast->failed_count > 0 && $broadcast->sent_count === 0 ? 'failed' : 'completed',
            'sent_at' => $broadcast->sent_at ?? now(),
        ]);
    }
}
