<?php

namespace App\Http\Controllers\Webhook;

use App\Events\Omnichannel\ConversationUpdated;
use App\Events\Omnichannel\MessageReceived;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\OmnichannelMessage;
use App\Models\User;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppBroadcastReply;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppProvider;
use App\Services\WhatsApp\WhatsAppConversationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FonnteWebhookController extends Controller
{
    public function __invoke(Request $request, WhatsAppConversationService $conversationService): JsonResponse
    {
        if (! $this->hasValidSecret($request)) {
            return response()->json(['message' => 'Invalid webhook secret.'], 403);
        }

        $data = $request->validate([
            'sender' => ['nullable', 'string'],
            'from' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'number' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'sender_name' => ['nullable', 'string'],
            'message' => ['nullable', 'string'],
            'text' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'id' => ['nullable', 'string'],
            'message_id' => ['nullable', 'string'],
            'msgid' => ['nullable', 'string'],
            'timestamp' => ['nullable'],
            'received_at' => ['nullable'],
        ]);

        $rawPhone = $this->firstFilled($data, ['sender', 'from', 'phone', 'number']);
        $message = $this->firstFilled($data, ['message', 'text', 'body']);

        if ($rawPhone === null || $message === null) {
            return response()->json(['message' => 'Phone number and message are required.'], 422);
        }

        $phone = $conversationService->normalizePhoneNumber($rawPhone);
        $receivedAt = $this->resolveReceivedAt($this->firstFilled($data, ['received_at', 'timestamp']));
        $senderName = $this->firstFilled($data, ['sender_name', 'name']) ?? $phone;
        $customer = $conversationService->findOrCreateCustomerFromInbound($phone, $senderName, 'inbound webhook');
        $lead = $this->findOrCreateLeadFromInbound($phone, $senderName, $message, $receivedAt, $customer->id);
        $recipient = $this->findBroadcastRecipientByPhone($phone);

        if ($recipient !== null) {
            WhatsAppBroadcastReply::create([
                'whatsapp_broadcast_id' => $recipient->whatsapp_broadcast_id,
                'whatsapp_broadcast_recipient_id' => $recipient->id,
                'sender_name' => $senderName,
                'phone_number' => $phone,
                'message' => $message,
                'status' => 'unread',
                'received_at' => $receivedAt,
            ]);

            $recipient->update([
                'status' => 'replied',
                'replied_at' => $receivedAt,
            ]);
        }

        $omnichannelMessage = OmnichannelMessage::create([
            'customer_id' => $customer?->id,
            'lead_id' => $lead?->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'sender_name' => $senderName,
            'sender_contact' => $phone,
            'message' => $message,
            'status' => 'unread',
            'received_at' => $receivedAt,
        ]);

        $conversation = WhatsAppConversation::query()->updateOrCreate(
            ['phone_number' => $phone],
            [
                'customer_id' => $customer->id,
                'lead_id' => $lead?->id,
                'contact_name' => $customer?->name ?? $lead?->name ?? $senderName,
                'channel' => 'whatsapp',
                'last_message' => $message,
                'last_message_at' => $receivedAt,
                'status' => 'open',
            ],
        );
        $conversation->increment('unread_count');

        $whatsAppMessage = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'lead_id' => $lead?->id,
            'phone' => $phone,
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => $message,
            'provider_message_id' => $this->firstFilled($data, ['id', 'message_id', 'msgid']),
            'provider' => 'fonnte',
            'broadcast_id' => $recipient?->whatsapp_broadcast_id,
            'status' => 'delivered',
            'sent_at' => $receivedAt,
            'received_at' => $receivedAt,
        ]);

        MessageReceived::dispatch($conversation->id, $whatsAppMessage->id);
        ConversationUpdated::dispatch($conversation->id);

        return response()->json([
            'message' => 'Webhook received.',
            'omnichannel_message_id' => $omnichannelMessage->id,
            'whatsapp_conversation_id' => $conversation->id,
        ]);
    }

    protected function hasValidSecret(Request $request): bool
    {
        $secret = $request->header('X-Webhook-Secret')
            ?? $request->header('X-Fonnte-Secret')
            ?? $request->input('webhook_secret')
            ?? $request->input('secret');

        if ($secret === null || trim((string) $secret) === '') {
            return false;
        }

        return WhatsAppProvider::query()
            ->where('provider', 'fonnte')
            ->where('webhook_secret', $secret)
            ->exists();
    }

    protected function firstFilled(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    protected function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return $digits;
    }

    protected function resolveReceivedAt(?string $value): Carbon
    {
        if ($value === null) {
            return now();
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        return Carbon::parse($value);
    }

    protected function findOrCreateLeadFromInbound(string $phone, string $senderName, string $message, Carbon $receivedAt, int $customerId): Lead
    {
        $lead = $this->findLeadByPhone($phone);

        if ($lead !== null) {
            $lead->update([
                'customer_id' => $lead->customer_id ?: $customerId,
                'last_whatsapp_message' => $message,
                'last_whatsapp_at' => $receivedAt,
            ]);

            return $lead;
        }

        return Lead::create([
            'customer_id' => $customerId,
            'name' => $senderName !== $phone ? $senderName : "WhatsApp Lead {$phone}",
            'phone' => $phone,
            'whatsapp' => $phone,
            'source' => 'whatsapp',
            'lead_source' => 'whatsapp',
            'status' => 'new',
            'priority' => 'medium',
            'assigned_to' => $this->defaultSalesAssignee(),
            'last_whatsapp_message' => $message,
            'last_whatsapp_at' => $receivedAt,
            'notes' => 'Auto generated from WhatsApp inbound webhook.',
        ]);
    }

    protected function findLeadByPhone(string $phone): ?Lead
    {
        return Lead::query()
            ->get(['id', 'phone', 'whatsapp'])
            ->first(fn (Lead $lead) => collect([$lead->phone, $lead->whatsapp])
                ->filter()
                ->contains(fn (string $value) => $this->normalizePhoneNumber($value) === $phone));
    }

    protected function defaultSalesAssignee(): ?string
    {
        $user = User::role('sales')->orderBy('name')->first();

        return $user?->name;
    }

    protected function findBroadcastRecipientByPhone(string $phone): ?WhatsAppBroadcastRecipient
    {
        return WhatsAppBroadcastRecipient::query()
            ->latest('id')
            ->get()
            ->first(fn (WhatsAppBroadcastRecipient $recipient) => $this->normalizePhoneNumber($recipient->phone_number) === $phone);
    }
}
