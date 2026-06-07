<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OmnichannelMessage;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppProvider;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = (string) ($request->query('hub_mode') ?? $request->query->get('hub.mode', ''));
        $token = (string) ($request->query('hub_verify_token') ?? $request->query->get('hub.verify_token', ''));
        $challenge = (string) ($request->query('hub_challenge') ?? $request->query->get('hub.challenge', ''));

        if ($mode === 'subscribe' && $challenge !== '' && $this->hasValidVerifyToken($token)) {
            return response($challenge, 200);
        }

        return response('Invalid verify token.', 403);
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Meta WhatsApp webhook payload received', [
            'payload' => $payload,
        ]);

        $messages = $this->payloadItems($payload, 'messages');
        $statuses = $this->payloadItems($payload, 'statuses');
        $updatedStatuses = $this->handleStatuses($statuses);

        if ($messages === []) {
            return response()->json([
                'message' => 'Webhook received.',
                'updated_statuses' => $updatedStatuses,
            ]);
        }

        $created = [];

        foreach ($messages as $messageData) {
            if (! is_array($messageData)) {
                continue;
            }

            $text = $this->messageText($messageData);
            $rawPhone = (string) data_get($messageData, 'from', '');

            if ($rawPhone === '' || $text === null) {
                continue;
            }

            $phone = $this->normalizePhoneNumber($rawPhone);
            $receivedAt = $this->resolveReceivedAt(data_get($messageData, 'timestamp'));
            $senderName = $this->senderName($payload, $phone);
            $customer = $this->findCustomerByPhone($phone) ?? $this->findOrCreateCustomerFromInbound($phone, $senderName);

            $omnichannelMessage = OmnichannelMessage::create([
                'customer_id' => $customer->id,
                'channel' => 'whatsapp',
                'direction' => 'inbound',
                'sender_name' => $senderName,
                'sender_contact' => $phone,
                'message' => $text,
                'status' => 'unread',
                'received_at' => $receivedAt,
            ]);

            $conversation = WhatsAppConversation::query()->updateOrCreate(
                ['phone_number' => $phone],
                [
                    'customer_id' => $customer->id,
                    'contact_name' => $customer->name ?: $senderName,
                    'channel' => 'whatsapp',
                    'last_message' => $text,
                    'last_message_at' => $receivedAt,
                    'status' => 'open',
                ],
            );
            $conversation->increment('unread_count');

            WhatsAppMessage::create([
                'whatsapp_conversation_id' => $conversation->id,
                'customer_id' => $customer->id,
                'phone' => $phone,
                'direction' => 'inbound',
                'message_type' => 'inbound',
                'message' => $text,
                'provider_message_id' => data_get($messageData, 'id'),
                'provider' => 'meta',
                'status' => 'delivered',
                'sent_at' => $receivedAt,
                'received_at' => $receivedAt,
            ]);

            $created[] = [
                'omnichannel_message_id' => $omnichannelMessage->id,
                'whatsapp_conversation_id' => $conversation->id,
            ];
        }

        return response()->json([
            'message' => 'Webhook received.',
            'created' => $created,
            'updated_statuses' => $updatedStatuses,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, mixed>
     */
    protected function payloadItems(array $payload, string $key): array
    {
        $items = [];
        $entries = data_get($payload, 'entry', []);

        if (! is_array($entries)) {
            return $items;
        }

        foreach ($entries as $entry) {
            $changes = is_array($entry) ? data_get($entry, 'changes', []) : [];

            if (! is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                $valueItems = is_array($change) ? data_get($change, "value.{$key}", []) : [];

                if (is_array($valueItems)) {
                    array_push($items, ...$valueItems);
                }
            }
        }

        return $items;
    }

    /**
     * @param array<int, mixed> $statuses
     * @return array<int, array<string, mixed>>
     */
    protected function handleStatuses(array $statuses): array
    {
        $updated = [];

        foreach ($statuses as $statusData) {
            if (! is_array($statusData)) {
                continue;
            }

            $messageId = (string) data_get($statusData, 'id', '');
            $status = (string) data_get($statusData, 'status', '');

            if ($messageId === '' || ! in_array($status, ['sent', 'delivered', 'read', 'failed'], true)) {
                continue;
            }

            $timestamp = $this->resolveReceivedAt(data_get($statusData, 'timestamp'));
            $errorMessage = $this->statusErrorMessage($statusData);
            Log::info('Meta WhatsApp message status received', [
                'message_id' => $messageId,
                'status' => $status,
                'recipient_id' => data_get($statusData, 'recipient_id'),
                'error' => $errorMessage,
                'raw' => $statusData,
            ]);
            $messageUpdates = ['status' => $status];
            $recipientUpdates = ['status' => $status];

            if ($status === 'sent') {
                $messageUpdates['sent_at'] = $timestamp;
                $recipientUpdates['sent_at'] = $timestamp;
            } elseif ($status === 'delivered') {
                $messageUpdates['delivered_at'] = $timestamp;
                $recipientUpdates['delivered_at'] = $timestamp;
            } elseif ($status === 'read') {
                $messageUpdates['read_at'] = $timestamp;
                $recipientUpdates['read_at'] = $timestamp;
            } elseif ($status === 'failed') {
                $messageUpdates['failed_at'] = $timestamp;
                $messageUpdates['error_message'] = $errorMessage;
                $recipientUpdates['error_message'] = $errorMessage;
                $recipientUpdates['failed_reason'] = $errorMessage;
            }

            $message = WhatsAppMessage::query()
                ->where('provider', 'meta')
                ->where('provider_message_id', $messageId)
                ->first();

            if ($message !== null) {
                $message->update($messageUpdates);
            }

            $recipient = WhatsAppBroadcastRecipient::query()
                ->where('provider_message_id', $messageId)
                ->first();

            if ($recipient !== null) {
                $recipient->update($recipientUpdates);
                $recipient->broadcast?->refreshDeliveryStats();
            }

            $updated[] = [
                'message_id' => $messageId,
                'status' => $status,
                'message_updated' => $message !== null,
                'recipient_updated' => $recipient !== null,
            ];
        }

        return $updated;
    }

    protected function hasValidVerifyToken(string $token): bool
    {
        if (trim($token) === '') {
            return false;
        }

        return WhatsAppProvider::query()
            ->where('provider', 'meta')
            ->where('webhook_secret', $token)
            ->exists();
    }

    /**
     * @param array<string, mixed> $messageData
     */
    protected function messageText(array $messageData): ?string
    {
        $type = (string) ($messageData['type'] ?? '');
        $text = match ($type) {
            'text' => data_get($messageData, 'text.body'),
            'button' => data_get($messageData, 'button.text'),
            'interactive' => data_get($messageData, 'interactive.button_reply.title')
                ?? data_get($messageData, 'interactive.list_reply.title'),
            default => data_get($messageData, 'text.body'),
        };

        $text = is_string($text) ? trim($text) : null;

        return $text !== '' ? $text : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function senderName(array $payload, string $phone): string
    {
        $contacts = data_get($payload, 'entry.0.changes.0.value.contacts', []);

        if (is_array($contacts)) {
            foreach ($contacts as $contact) {
                if (! is_array($contact)) {
                    continue;
                }

                if ($this->normalizePhoneNumber((string) data_get($contact, 'wa_id', '')) === $phone) {
                    $name = trim((string) data_get($contact, 'profile.name', ''));

                    if ($name !== '') {
                        return $name;
                    }
                }
            }
        }

        return $phone;
    }

    protected function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        return $digits;
    }

    protected function resolveReceivedAt(mixed $value): Carbon
    {
        if ($value === null || $value === '') {
            return now();
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        return Carbon::parse((string) $value);
    }

    /**
     * @param array<string, mixed> $statusData
     */
    protected function statusErrorMessage(array $statusData): ?string
    {
        $message = data_get($statusData, 'errors.0.message')
            ?? data_get($statusData, 'errors.0.error_data.details')
            ?? data_get($statusData, 'errors.0.title');

        return is_string($message) && trim($message) !== '' ? $message : null;
    }

    protected function findCustomerByPhone(string $phone): ?Customer
    {
        return Customer::query()
            ->get(['id', 'phone', 'whatsapp'])
            ->first(fn (Customer $customer) => collect([$customer->phone, $customer->whatsapp])
                ->filter()
                ->contains(fn (string $value) => $this->normalizePhoneNumber($value) === $phone));
    }

    protected function findOrCreateCustomerFromInbound(string $phone, string $senderName): Customer
    {
        return Customer::query()->firstOrCreate(
            ['whatsapp' => $phone],
            [
                'name' => $senderName !== $phone ? $senderName : "WhatsApp Customer {$phone}",
                'phone' => $phone,
                'source' => 'whatsapp',
                'status' => 'new',
                'notes' => 'Auto generated from WhatsApp Meta webhook.',
            ],
        );
    }

}
