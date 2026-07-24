<?php

namespace App\Http\Requests\Admin;

use App\Services\ReferenceData\ReferenceDataService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerSatisfactionStoreRequest extends FormRequest
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
        return [
            'ticket_id' => [
                'nullable',
                Rule::exists('tickets', 'id')
                    ->where(fn ($query) => $query->where('customer_id', $this->input('customer_id'))),
            ],
            'customer_id' => [
                'nullable',
                Rule::requiredIf($this->filled('ticket_id')),
                'exists:customers,id',
            ],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string'],
            'survey_channel' => ['required', Rule::in($this->channelOptions())],
            'sentiment' => ['required', Rule::in($this->sentimentOptions())],
            'submitted_at' => ['nullable', 'date'],
            'follow_up_required' => ['required', 'boolean'],
            'follow_up_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->validateSurveyChannelCapability($validator);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function satisfactionData(): array
    {
        $validated = $this->validated();
        $validated['ticket_id'] = $validated['ticket_id'] ?? null;
        $validated['customer_id'] = $validated['customer_id'] ?? null;

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function sentimentOptions(): array
    {
        return ['positive', 'neutral', 'negative'];
    }

    /**
     * @return array<int, string>
     */
    protected function channelOptions(): array
    {
        return ['email', 'whatsapp', 'phone', 'web'];
    }

    protected function validateSurveyChannelCapability($validator): void
    {
        $channel = (string) $this->input('survey_channel', '');

        if ($channel === '' || $channel === $this->existingSurveyChannel()) {
            return;
        }

        $referenceData = app(ReferenceDataService::class);

        if (! $referenceData->hasOptions(ReferenceDataService::TYPE_SERVICE_CHANNEL, 'csat_survey')) {
            return;
        }

        if (! $referenceData->isValidActiveCode(ReferenceDataService::TYPE_SERVICE_CHANNEL, $channel, 'csat_survey')) {
            $validator->errors()->add('survey_channel', 'The selected survey channel is not available for customer satisfaction.');
        }
    }

    protected function existingSurveyChannel(): ?string
    {
        return null;
    }
}
