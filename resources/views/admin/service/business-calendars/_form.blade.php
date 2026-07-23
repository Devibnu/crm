@php
    $calendar = $calendar ?? null;
    $selectedTimezone = old('timezone', $calendar->timezone ?? 'Asia/Jakarta');
    $selectedActive = old('is_active', isset($calendar) ? (int) $calendar->is_active : 1);
    $selectedDefault = old('is_default', isset($calendar) ? (int) $calendar->is_default : 0);
    $hoursSource = old('working_hours');
    $hoursByDay = collect($hoursSource ?? [])
        ->mapWithKeys(fn ($day) => [(int) ($day['day_of_week'] ?? 0) => $day]);

    if ($hoursSource === null && $calendar?->relationLoaded('workingHours')) {
        $hoursByDay = $calendar->workingHours
            ->mapWithKeys(fn ($day) => [$day->day_of_week => [
                'day_of_week' => $day->day_of_week,
                'is_working_day' => $day->is_working_day ? 1 : 0,
                'start_time' => $day->start_time ? substr($day->start_time, 0, 5) : null,
                'end_time' => $day->end_time ? substr($day->end_time, 0, 5) : null,
            ]]);
    }

    $defaultDay = fn (int $day): array => [
        'day_of_week' => $day,
        'is_working_day' => $day <= 5 ? 1 : 0,
        'start_time' => $day <= 5 ? '08:00' : null,
        'end_time' => $day <= 5 ? '17:00' : null,
    ];
@endphp

<div class="lead-form-groups customer-form-groups">
    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Calendar Identity</h2>
            <p>Name and context used by service operations.</p>
        </div>
        <div class="lead-form-grid">
            <label class="field">
                <span>Calendar Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $calendar->name ?? '') }}" maxlength="255" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Description</span>
                <textarea name="description" rows="4">{{ old('description', $calendar->description ?? '') }}</textarea>
                @error('description')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Timezone and Status</h2>
            <p>Calendar state and IANA timezone for business-time evaluation.</p>
        </div>
        <div class="lead-form-grid">
            <label class="field">
                <span>Timezone <strong>*</strong></span>
                <select name="timezone" required>
                    @foreach ($timezoneOptions as $timezone)
                        <option value="{{ $timezone }}" @selected($selectedTimezone === $timezone)>{{ $timezone }}</option>
                    @endforeach
                </select>
                @error('timezone')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Active Status <strong>*</strong></span>
                <select name="is_active" required>
                    <option value="1" @selected((string) $selectedActive === '1')>Active</option>
                    <option value="0" @selected((string) $selectedActive === '0')>Inactive</option>
                </select>
                @error('is_active')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Default Calendar</span>
                <select name="is_default">
                    <option value="0" @selected((string) $selectedDefault === '0')>No</option>
                    <option value="1" @selected((string) $selectedDefault === '1')>Yes</option>
                </select>
                @error('is_default')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Weekly Operating Hours</h2>
            <p>Configure Monday through Sunday using ISO weekday numbering.</p>
        </div>
        @error('working_hours')<small class="error">{{ $message }}</small>@enderror
        <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
            <table class="customer-table lead-modern-table sales-table">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Working Day</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dayLabels as $day => $label)
                        @php
                            $dayConfig = $hoursByDay->get($day, $defaultDay($day));
                            $isWorkingDay = (string) ($dayConfig['is_working_day'] ?? 0) === '1';
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $label }}</strong>
                                <small>ISO {{ $day }}</small>
                                <input type="hidden" name="working_hours[{{ $day }}][day_of_week]" value="{{ $day }}">
                            </td>
                            <td>
                                <input type="hidden" name="working_hours[{{ $day }}][is_working_day]" value="0">
                                <label class="field" style="margin:0;">
                                    <span class="sr-only">{{ $label }} working day</span>
                                    <input type="checkbox" name="working_hours[{{ $day }}][is_working_day]" value="1" @checked($isWorkingDay) data-business-day-toggle="{{ $day }}">
                                </label>
                                @error("working_hours.{$day}.is_working_day")<small class="error">{{ $message }}</small>@enderror
                            </td>
                            <td>
                                <input type="time" name="working_hours[{{ $day }}][start_time]" value="{{ $dayConfig['start_time'] ? substr($dayConfig['start_time'], 0, 5) : '' }}" @disabled(! $isWorkingDay) data-business-time="{{ $day }}">
                                @error("working_hours.{$day}.start_time")<small class="error">{{ $message }}</small>@enderror
                            </td>
                            <td>
                                <input type="time" name="working_hours[{{ $day }}][end_time]" value="{{ $dayConfig['end_time'] ? substr($dayConfig['end_time'], 0, 5) : '' }}" @disabled(! $isWorkingDay) data-business-time="{{ $day }}">
                                @error("working_hours.{$day}.end_time")<small class="error">{{ $message }}</small>@enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('[data-business-day-toggle]').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                document.querySelectorAll(`[data-business-time="${toggle.dataset.businessDayToggle}"]`).forEach((input) => {
                    input.disabled = !toggle.checked;
                    if (!toggle.checked) input.value = '';
                });
            });
        });
    </script>
@endpush
