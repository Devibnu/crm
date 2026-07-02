<?php

namespace App\Services\WhatsApp;

use App\Events\Omnichannel\ConversationAssigned;
use App\Events\Omnichannel\ConversationResolved;
use App\Events\Omnichannel\ConversationUpdated;
use App\Events\Omnichannel\MessageReceived;
use App\Events\Omnichannel\MessageSent;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            Log::info('WhatsApp inbound Meta message parsed.', [
                'phone' => $phone,
                'message_type' => $messageType,
                'provider_message_id' => $providerMessageId,
            ]);

            $customer = $this->findCustomerByPhone($phone);
            $lead = $customer ? null : $this->findLeadByPhone($phone);

            if ($lead !== null) {
                $lead->update([
                    'last_whatsapp_message' => $messageBody,
                    'last_whatsapp_at' => $receivedAt,
                ]);
            }

            $existingConversation = WhatsAppConversation::query()
                ->where('phone_number', $phone)
                ->first();

            $conversation = WhatsAppConversation::query()->updateOrCreate(
                ['phone_number' => $phone],
                [
                    'customer_id' => $customer?->id,
                    'lead_id' => $lead?->id,
                    'contact_name' => $customer?->name ?: $lead?->name ?: $customerName,
                    'channel' => 'whatsapp',
                    'last_message' => $messageBody,
                    'last_message_at' => $receivedAt,
                    'status' => 'open',
                ],
            );
            $conversation->increment('unread_count');
            $conversation->refresh();

            Log::info('WhatsApp inbound Meta conversation saved.', [
                'conversation_id' => $conversation->id,
                'phone' => $phone,
                'created' => $existingConversation === null,
                'customer_id' => $customer?->id,
                'lead_id' => $lead?->id,
            ]);

            $message = WhatsAppMessage::query()->create($this->messageAttributes([
                'whatsapp_conversation_id' => $conversation->id,
                'customer_id' => $customer?->id,
                'lead_id' => $lead?->id,
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

            Log::info('WhatsApp inbound Meta message saved.', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'provider_message_id' => $providerMessageId,
                'phone' => $phone,
            ]);

            MessageReceived::dispatch($conversation->id, $message->id);
            ConversationUpdated::dispatch($conversation->id);

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

            MessageSent::dispatch($conversation->id, $message->id);
            ConversationUpdated::dispatch($conversation->id);

            return $message;
        });
    }

    /**
     * @param array<string, mixed> $media
     * @param array<string, mixed> $providerResult
     */
    public function recordOutgoingMediaReply(WhatsAppConversation $conversation, array $media, array $providerResult): WhatsAppMessage
    {
        return DB::transaction(function () use ($conversation, $media, $providerResult): WhatsAppMessage {
            $success = (bool) ($providerResult['success'] ?? false);
            $messageBody = trim((string) ($media['caption'] ?? '')) ?: (string) ($media['original_name'] ?? 'Attachment');

            $message = WhatsAppMessage::query()->create($this->messageAttributes([
                'whatsapp_conversation_id' => $conversation->id,
                'customer_id' => $conversation->customer_id,
                'lead_id' => $conversation->lead_id,
                'phone' => $conversation->phone_number,
                'direction' => 'outbound',
                'message_type' => $this->messageTypeForStorage('outbound', (string) ($providerResult['message_type'] ?? $media['type'] ?? 'document')),
                'message' => $messageBody,
                'provider_message_id' => $providerResult['message_id'] ?? null,
                'provider' => $providerResult['provider'] ?? null,
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => now(),
                'failed_at' => $success ? null : now(),
                'error_message' => $success ? null : $this->errorMessageFromProviderResult($providerResult),
                'raw_payload' => $providerResult['raw'] ?? null,
                'media_path' => $media['path'] ?? null,
                'media_original_name' => $media['original_name'] ?? null,
                'media_mime' => $media['mime'] ?? null,
                'media_size' => $media['size'] ?? null,
                'media_id' => $providerResult['media_id'] ?? null,
                'media_url' => $media['url'] ?? null,
            ]));

            $conversation->update([
                'last_message' => $messageBody,
                'last_message_at' => now(),
            ]);

            MessageSent::dispatch($conversation->id, $message->id);
            ConversationUpdated::dispatch($conversation->id);

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

            $freshConversation = $conversation->fresh();

            ConversationAssigned::dispatch($freshConversation->id, $freshConversation->assigned_to);
            ConversationUpdated::dispatch($freshConversation->id);

            return $freshConversation;
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

            $freshConversation = $conversation->fresh();

            ConversationResolved::dispatch($freshConversation->id, $freshConversation->status);
            ConversationUpdated::dispatch($freshConversation->id);

            return $freshConversation;
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

    protected function findLeadByPhone(string $phone): ?Lead
    {
        return Lead::query()
            ->get(['id', 'phone', 'whatsapp'])
            ->first(fn (Lead $lead) => collect([$lead->phone, $lead->whatsapp])
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

        foreach (['media_path', 'media_original_name', 'media_mime', 'media_size', 'media_id', 'media_url'] as $column) {
            if (! Schema::hasColumn('whatsapp_messages', $column)) {
                unset($attributes[$column]);
            }
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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $columnDefinition = DB::selectOne("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
            $type = (string) ($columnDefinition->Type ?? $columnDefinition->type ?? '');

            if (! str_starts_with(strtolower($type), 'enum(')) {
                return true;
            }

            return $this->enumDefinitionAllowsValue($type, $value);
        }

        if ($driver === 'pgsql') {
            $constraints = DB::select(
                <<<'SQL'
                select pg_get_constraintdef(c.oid) as definition
                from pg_constraint c
                join pg_class t on c.conrelid = t.oid
                join pg_namespace n on n.oid = t.relnamespace
                where t.relname = ?
                    and n.nspname = current_schema()
                    and c.contype = 'c'
                SQL,
                [$table],
            );

            foreach ($constraints as $constraint) {
                $definition = (string) ($constraint->definition ?? '');

                if (str_contains($definition, $column)) {
                    return str_contains($definition, "'{$value}'");
                }
            }

            return true;
        }

        if ($driver === 'sqlite') {
            $definition = (string) DB::selectOne(
                "select sql from sqlite_master where type = 'table' and name = ?",
                [$table],
            )?->sql;

            if ($definition === '' || ! str_contains($definition, 'CHECK') || ! str_contains($definition, $column)) {
                return true;
            }

            return str_contains($definition, "'{$value}'");
        }

        return true;
    }

    protected function enumDefinitionAllowsValue(string $definition, string $value): bool
    {
        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $definition, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $enumValue): string => stripcslashes($enumValue))
            ->contains($value);
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
