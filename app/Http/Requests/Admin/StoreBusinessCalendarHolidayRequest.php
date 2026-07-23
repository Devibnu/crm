<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessCalendarHolidayRequest extends FormRequest
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
        $calendar = $this->route('business_calendar');

        return [
            'holiday_date' => [
                'required',
                'date',
                Rule::unique('business_calendar_holidays', 'holiday_date')
                    ->where('business_calendar_id', $calendar?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_recurring' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function holidayData(): array
    {
        $validated = $this->validated();

        return [
            'holiday_date' => $validated['holiday_date'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
        ];
    }
}
