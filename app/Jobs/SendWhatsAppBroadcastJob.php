<?php

namespace App\Jobs;

use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
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

        $result = $manager->sendMessage(
            $phone,
            $this->personalizedMessage($broadcast, $recipient),
        );

        Log::info('WhatsApp broadcast provider result received', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'broadcast_status' => $broadcast->status,
            'recipient_status' => $recipient->status,
            'normalized_phone' => $phone,
            'provider_success' => (bool) ($result['success'] ?? false),
            'provider_message_id' => $result['message_id'] ?? null,
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

        $broadcast->refreshDeliveryStats();
        $this->completeBroadcastIfFinished($broadcast->fresh());

        Log::info('WhatsApp broadcast recipient marked sent', [
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
            'broadcast_status' => $broadcast->fresh()?->status,
            'recipient_status' => 'sent',
            'normalized_phone' => $phone,
            'provider_message_id' => $result['message_id'] ?? null,
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

    /**
     * @param array<string, mixed> $result
     */
    protected function errorMessageFromResult(array $result): string
    {
        $raw = $result['raw'] ?? [];

        if (is_array($raw)) {
            return (string) ($raw['reason'] ?? $raw['error'] ?? $raw['message'] ?? 'WhatsApp provider failed.');
        }

        return 'WhatsApp provider failed.';
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
