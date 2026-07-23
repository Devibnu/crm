<?php

namespace App\Services\Sla;

use App\Models\BusinessCalendar;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use Illuminate\Validation\ValidationException;

class SlaPolicyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SlaPolicy
    {
        $this->assertResolutionTargetIsValid($data);
        $this->assertWarningPercentagesAreValid($data);
        $this->assertActivePriorityIsUnique($data);
        $this->assertBusinessCalendarIsActive($data);

        return SlaPolicy::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SlaPolicy $policy, array $data): SlaPolicy
    {
        $this->assertResolutionTargetIsValid($data);
        $this->assertWarningPercentagesAreValid($data);
        $this->assertActivePriorityIsUnique($data, $policy);
        $this->assertBusinessCalendarIsActive($data);

        $policy->update($data);

        return $policy->refresh();
    }

    public function activate(SlaPolicy $policy): SlaPolicy
    {
        return $this->update($policy, ['is_active' => true] + $policy->only([
            'name',
            'description',
            'business_calendar_id',
            'priority',
            'response_time_minutes',
            'response_warning_percentage',
            'resolution_time_minutes',
            'resolution_warning_percentage',
        ]));
    }

    public function deactivate(SlaPolicy $policy): SlaPolicy
    {
        $policy->update(['is_active' => false]);

        return $policy->refresh();
    }

    public function delete(SlaPolicy $policy): void
    {
        $this->assertPolicyIsNotUsedByActiveTickets($policy);

        $policy->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertResolutionTargetIsValid(array $data): void
    {
        $responseTarget = (int) ($data['response_time_minutes'] ?? 0);
        $resolutionTarget = (int) ($data['resolution_time_minutes'] ?? 0);

        if ($responseTarget <= 0) {
            throw ValidationException::withMessages([
                'response_time_minutes' => 'The response target must be greater than zero.',
            ]);
        }

        if ($resolutionTarget <= $responseTarget) {
            throw ValidationException::withMessages([
                'resolution_time_minutes' => 'The resolution target must be greater than the response target.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertActivePriorityIsUnique(array $data, ?SlaPolicy $except = null): void
    {
        if (! (bool) ($data['is_active'] ?? false)) {
            return;
        }

        $priority = (string) ($data['priority'] ?? '');

        if ($priority === '') {
            return;
        }

        $duplicate = SlaPolicy::query()
            ->where('priority', $priority)
            ->where('is_active', true)
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'priority' => 'An active SLA policy already exists for this priority.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertWarningPercentagesAreValid(array $data): void
    {
        foreach (['response_warning_percentage', 'resolution_warning_percentage'] as $field) {
            if (! array_key_exists($field, $data) || blank($data[$field])) {
                continue;
            }

            $percentage = (int) $data[$field];

            if ($percentage < 1 || $percentage > 99) {
                throw ValidationException::withMessages([
                    $field => 'The warning percentage must be between 1 and 99.',
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertBusinessCalendarIsActive(array $data): void
    {
        if (! array_key_exists('business_calendar_id', $data) || blank($data['business_calendar_id'])) {
            return;
        }

        $isActive = BusinessCalendar::query()
            ->whereKey($data['business_calendar_id'])
            ->where('is_active', true)
            ->exists();

        if (! $isActive) {
            throw ValidationException::withMessages([
                'business_calendar_id' => 'The selected business calendar must be active.',
            ]);
        }
    }

    protected function assertPolicyIsNotUsedByActiveTickets(SlaPolicy $policy): void
    {
        if (! $policy->is_active) {
            return;
        }

        $isUsed = Ticket::query()
            ->where('priority', $policy->priority)
            ->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'reopened'])
            ->exists();

        if ($isUsed) {
            throw ValidationException::withMessages([
                'policy' => 'This SLA policy is still used by active tickets and cannot be deleted.',
            ]);
        }
    }
}
