<?php

namespace App\Http\Requests\Admin;

use App\Models\SlaPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSlaPolicyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'business_calendar_id' => [
                'required',
                'integer',
                Rule::exists('business_calendars', 'id')->where('is_active', true),
            ],
            'priority' => ['required', Rule::in(SlaPolicy::priorityOptions())],
            'response_time_minutes' => ['required', 'integer', 'min:1'],
            'response_warning_percentage' => ['nullable', 'integer', 'min:1', 'max:99'],
            'resolution_time_minutes' => ['required', 'integer', 'min:1'],
            'resolution_warning_percentage' => ['nullable', 'integer', 'min:1', 'max:99'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_calendar_id.required' => 'Please select an active business calendar for this SLA policy.',
            'business_calendar_id.exists' => 'The selected business calendar must be active.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $this->validateResolutionTarget($validator);
            $this->validateActivePriority($validator);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function policyData(): array
    {
        return $this->validated();
    }

    protected function validateResolutionTarget($validator): void
    {
        $responseTarget = $this->integer('response_time_minutes');
        $resolutionTarget = $this->integer('resolution_time_minutes');

        if ($resolutionTarget > 0 && $responseTarget > 0 && $resolutionTarget <= $responseTarget) {
            $validator->errors()->add('resolution_time_minutes', 'The resolution target must be greater than the response target.');
        }
    }

    protected function validateActivePriority($validator): void
    {
        if (! $this->boolean('is_active')) {
            return;
        }

        $priority = (string) $this->input('priority');
        $policy = $this->route('sla');

        if ($priority === '') {
            return;
        }

        $duplicate = SlaPolicy::query()
            ->where('priority', $priority)
            ->where('is_active', true)
            ->when($policy instanceof SlaPolicy, fn ($query) => $query->whereKeyNot($policy->getKey()))
            ->exists();

        if ($duplicate) {
            $validator->errors()->add('priority', 'An active SLA policy already exists for this priority.');
        }
    }
}
