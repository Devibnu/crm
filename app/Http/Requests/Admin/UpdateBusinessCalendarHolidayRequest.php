<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateBusinessCalendarHolidayRequest extends StoreBusinessCalendarHolidayRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $calendar = $this->route('business_calendar');
        $holiday = $this->route('holiday');

        return [
            'holiday_date' => [
                'required',
                'date',
                Rule::unique('business_calendar_holidays', 'holiday_date')
                    ->where('business_calendar_id', $calendar?->id)
                    ->ignore($holiday?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_recurring' => ['nullable', 'boolean'],
        ];
    }
}
