<?php

namespace App\Http\Requests\Admin;

use App\Models\Ticket;
use App\Models\WhatsAppConversation;
use App\Services\ReferenceData\ReferenceDataService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(Ticket::priorityOptions())],
            'status' => ['required', Rule::in(Ticket::statusOptions())],
            'channel' => ['required', Rule::in(Ticket::channelOptions())],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
        ];

        if (Schema::hasColumn('tickets', 'conversation_id')) {
            $rules['conversation_id'] = ['nullable', 'exists:whatsapp_conversations,id'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $conversationId = $this->integer('conversation_id');
            $customerId = $this->integer('customer_id');

            if (! $conversationId || ! $customerId) {
                $this->validateChannelCapability($validator);

                return;
            }

            $conversationCustomerId = WhatsAppConversation::query()
                ->whereKey($conversationId)
                ->value('customer_id');

            if ($conversationCustomerId && (int) $conversationCustomerId !== $customerId) {
                $validator->errors()->add('conversation_id', 'The selected conversation does not belong to the selected customer.');
            }

            $this->validateChannelCapability($validator);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function ticketData(): array
    {
        $validated = $this->validated();
        $validated['customer_id'] = $validated['customer_id'] ?? null;

        if (array_key_exists('conversation_id', $validated)) {
            $validated['conversation_id'] = $validated['conversation_id'] ?: null;
        }

        return $validated;
    }

    protected function validateChannelCapability($validator): void
    {
        $channel = (string) $this->input('channel', '');

        if ($channel === '') {
            return;
        }

        $referenceData = app(ReferenceDataService::class);

        if (! $referenceData->hasOptions(ReferenceDataService::TYPE_SERVICE_CHANNEL, 'service_ticket')) {
            return;
        }

        if (! $referenceData->isValidActiveCode(ReferenceDataService::TYPE_SERVICE_CHANNEL, $channel, 'service_ticket')) {
            $validator->errors()->add('channel', 'The selected channel is not available for service tickets.');
        }
    }
}
