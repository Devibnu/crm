<?php

namespace App\Services\ReferenceData;

use App\Models\ReferenceValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ReferenceDataService
{
    public const TYPE_SERVICE_CHANNEL = 'service_channel';

    /**
     * @return array<string, string>
     */
    public function options(string $typeCode, ?string $capability = null, bool $activeOnly = true): array
    {
        if (! $this->tablesAvailable()) {
            return [];
        }

        $cacheKey = sprintf('reference_data.options.%s.%s.%s', $typeCode, $capability ?: 'all', $activeOnly ? 'active' : 'all');

        try {
            return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($typeCode, $capability, $activeOnly): array {
                return $this->queryValues($typeCode, $capability, $activeOnly)
                    ->ordered()
                    ->pluck('label', 'code')
                    ->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    public function hasOptions(string $typeCode, ?string $capability = null): bool
    {
        return $this->options($typeCode, $capability) !== [];
    }

    public function value(string $typeCode, string $code): ?ReferenceValue
    {
        if (! $this->tablesAvailable()) {
            return null;
        }

        try {
            return ReferenceValue::query()
                ->with(['type', 'capabilities'])
                ->where('code', $code)
                ->whereHas('type', fn (Builder $query) => $query->where('code', $typeCode))
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    public function label(string $typeCode, string $code, ?string $fallback = null): string
    {
        return $this->value($typeCode, $code)?->label
            ?: $fallback
            ?: Str::headline(str_replace('_', ' ', $code));
    }

    public function isValidActiveCode(string $typeCode, string $code, ?string $capability = null): bool
    {
        if (! $this->tablesAvailable()) {
            return false;
        }

        try {
            return $this->queryValues($typeCode, $capability, true)
                ->where('reference_values.code', $code)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function allowsCapability(string $typeCode, string $code, string $capability): bool
    {
        return (bool) $this->value($typeCode, $code)?->allowsCapability($capability);
    }

    protected function queryValues(string $typeCode, ?string $capability = null, bool $activeOnly = true): Builder
    {
        return ReferenceValue::query()
            ->whereHas('type', fn (Builder $query) => $query->where('code', $typeCode)->where('is_active', true))
            ->when($activeOnly, fn (Builder $query) => $query->active())
            ->when($capability, fn (Builder $query) => $query->whereHas('capabilities', fn (Builder $capabilityQuery) => $capabilityQuery->where('capability', $capability)));
    }

    protected function tablesAvailable(): bool
    {
        try {
            return Schema::hasTable('reference_types')
                && Schema::hasTable('reference_values')
                && Schema::hasTable('reference_value_capabilities');
        } catch (Throwable) {
            return false;
        }
    }
}
