<?php

namespace App\Http\Requests\Admin;

use App\Models\BusinessCalendar;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBusinessCalendarRequest extends FormRequest
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
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'working_hours' => ['required', 'array', 'size:7'],
            'working_hours.*.day_of_week' => ['required', 'integer', Rule::in(array_keys(BusinessCalendar::ISO_DAYS))],
            'working_hours.*.is_working_day' => ['required', 'boolean'],
            'working_hours.*.start_time' => ['nullable', 'date_format:H:i'],
            'working_hours.*.end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hours = $this->input('working_hours', []);
            $days = collect($hours)->pluck('day_of_week')->map(fn ($day) => (int) $day)->sort()->values()->all();

            if ($days !== array_keys(BusinessCalendar::ISO_DAYS)) {
                $validator->errors()->add('working_hours', 'The weekly schedule must contain exactly Monday through Sunday.');
            }

            foreach ($hours as $index => $day) {
                $isWorkingDay = filter_var($day['is_working_day'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $startTime = $day['start_time'] ?? null;
                $endTime = $day['end_time'] ?? null;

                if ($isWorkingDay && (! $startTime || ! $endTime)) {
                    $validator->errors()->add("working_hours.{$index}.start_time", 'Working days require start and end times.');
                }

                if ($isWorkingDay && $startTime && $endTime && $endTime <= $startTime) {
                    $validator->errors()->add("working_hours.{$index}.end_time", 'End time must be after start time.');
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function calendarData(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'timezone' => $validated['timezone'],
            'is_active' => (bool) $validated['is_active'],
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function workingHoursData(): array
    {
        return collect($this->validated('working_hours'))
            ->map(function (array $day): array {
                $isWorkingDay = (bool) $day['is_working_day'];

                return [
                    'day_of_week' => (int) $day['day_of_week'],
                    'is_working_day' => $isWorkingDay,
                    'start_time' => $isWorkingDay ? $day['start_time'] : null,
                    'end_time' => $isWorkingDay ? $day['end_time'] : null,
                ];
            })
            ->sortBy('day_of_week')
            ->values()
            ->all();
    }
}
