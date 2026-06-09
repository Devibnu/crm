<?php

namespace App\Services\WhatsApp;

use App\Models\Customer;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsAppConversationService
{
    /**
     * @param array<string, mixed> $rawPayload
     * @return array{conversation:WhatsAppConversation, message:?WhatsAppMessage, duplicate:bool}
     */
    public function recordIncomingMetaMessage(
        string $phone,
        string $customerName,
        string $messageBody,
        string $messageType,
        ?string $providerMessageId,
        CarbonInterface $receivedAt,
        array $rawPayload = [],
    ): array {
        return DB::transaction(function () use ($phone, $customerName, $messageBody, $messageType, $providerMessageId, $receivedAt, $rawPayload): array {
            if ($providerMessageId !== null && $providerMessageId !== '') {
                $existingMessage = WhatsAppMessage::query()
                    ->where('provider', 'meta')
                    ->where('provider_message_id', $providerMessageId)
                    ->first();

                if ($existingMessage !== null) {
                    return [
                        'conversation' => $existingMessage->conversation,
                        'message' => $existingMessage,
                        'duplicate' => true,
                    ];
                }
            }

            $customer = $this->findCustomerByPhone($phone) ?? $this->findOrCreateCustomerFromInbound($phone, $customerName);

            $conversation = WhatsAppConversation::query()->updateOrCreate(
                ['phone_number' => $phone],
                [
                    'customer_id' => $customer->id,
                    'contact_name' => $customer->name ?: $customerName,
                    'channel' => 'whatsapp',
                    'last_message' => $messageBody,
                    'last_message_at' => $receivedAt,
                    'status' => 'open',
                ],
            );
            $conversation->increment('unread_count');
            $conversation->refresh();

            $message = WhatsAppMessage::query()->create($this->messageAttributes([
                'whatsapp_conversation_id' => $conversation->id,
                'customer_id' => $customer->id,
                    'phone' => $phone,
                    'direction' => 'inbound',
                    'message_type' => $this->messageTypeForStorage('inbound', $messageType),
                'message' => $messageBody,
                'provider_message_id' => $providerMessageId,
                'provider' => 'meta',
                'status' => 'delivered',
                'sent_at' => $receivedAt,
                'received_at' => $receivedAt,
                'raw_payload' => $rawPayload,
            ]));

            return [
                'conversation' => $conversation,
                'message' => $message,
                'duplicate' => false,
            ];
        });
    }

    /**
     * @param array<string, mixed> $providerResult
     */
    public function recordOutgoingReply(WhatsAppConversation $conversation, string $messageBody, array $providerResult): WhatsAppMessage
    {
        return DB::transaction(function () use ($conversation, $messageBody, $providerResult): WhatsAppMessage {
            $success = (bool) ($providerResult['success'] ?? false);
            $message = WhatsAppMessage::query()->create($this->messageAttributes([
                'whatsapp_conversation_id' => $conversation->id,
                'customer_id' => $conversation->customer_id,
                'lead_id' => $conversation->lead_id,
                'phone' => $conversation->phone_number,
                'direction' => 'outbound',
                'message_type' => $this->messageTypeForStorage('outbound', (string) ($providerResult['message_type'] ?? 'text')),
                'message' => $messageBody,
                'provider_message_id' => $providerResult['message_id'] ?? null,
                'provider' => $providerResult['provider'] ?? null,
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => now(),
                'failed_at' => $success ? null : now(),
                'error_message' => $success ? null : $this->errorMessageFromProviderResult($providerResult),
                'raw_payload' => $providerResult['raw'] ?? null,
            ]));

            $conversation->update([
                'last_message' => $messageBody,
                'last_message_at' => now(),
            ]);

            return $message;
        });
    }

    public function assignToAgent(WhatsAppConversation $conversation, string $agentName): WhatsAppConversation
    {
        return DB::transaction(function () use ($conversation, $agentName): WhatsAppConversation {
            $conversation->update([
                'assigned_to' => $agentName,
                'taken_at' => now(),
                'status' => $conversation->status === 'resolved' ? 'open' : ($conversation->status ?: 'open'),
            ]);

            return $conversation->fresh();
        });
    }

    public function markResolved(WhatsAppConversation $conversation): WhatsAppConversation
    {
        return DB::transaction(function () use ($conversation): WhatsAppConversation {
            $conversation->update([
                'status' => $this->conversationStatusForStorage('resolved'),
                'closed_at' => now(),
                'unread_count' => 0,
            ]);

            return $conversation->fresh();
        });
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

    protected function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    protected function messageAttributes(array $attributes): array
    {
        if (! Schema::hasColumn('whatsapp_messages', 'raw_payload')) {
            unset($attributes['raw_payload']);
        }

        return $attributes;
    }

    protected function messageTypeForStorage(string $direction, string $messageType): string
    {
        if (DB::connection()->getDriverName() === 'sqlite' && ! in_array($messageType, ['inbound', 'outbound'], true)) {
            return $direction === 'outbound' ? 'outbound' : 'inbound';
        }

        if ($this->columnAllowsValue('whatsapp_messages', 'message_type', $messageType)) {
            return $messageType;
        }

        return $direction === 'outbound' ? 'outbound' : 'inbound';
    }

    protected function conversationStatusForStorage(string $status): string
    {
        if (DB::connection()->getDriverName() === 'sqlite' && $status === 'resolved') {
            return 'closed';
        }

        if ($this->columnAllowsValue('whatsapp_conversations', 'status', $status)) {
            return $status;
        }

        return $status === 'resolved' ? 'closed' : $status;
    }

    protected function columnAllowsValue(string $table, string $column, string $value): bool
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return true;
        }

        $definition = (string) DB::selectOne(
            "select sql from sqlite_master where type = 'table' and name = ?",
            [$table],
        )?->sql;

        if ($definition === '' || ! str_contains($definition, 'CHECK') || ! str_contains($definition, $column)) {
            return true;
        }

        return str_contains($definition, "'{$value}'");
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function errorMessageFromProviderResult(array $result): string
    {
        $raw = $result['raw'] ?? [];

        if (is_array($raw)) {
            return (string) ($result['reason'] ?? $raw['reason'] ?? $raw['error'] ?? $raw['message'] ?? 'WhatsApp provider failed.');
        }

        return (string) ($result['reason'] ?? 'WhatsApp provider failed.');
    }
}
