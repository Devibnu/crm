<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    private const STAGES = ['new', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];

    public function index(): JsonResponse
    {
        $opportunities = Opportunity::query()
            ->with('lead:id,full_name,company')
            ->latest()
            ->get();

        $openOpportunities = $opportunities->whereNotIn('stage', ['closed_won', 'closed_lost'])->values();

        $pipelineValue = (float) $openOpportunities->sum('amount');
        $weightedForecast = (float) $openOpportunities->sum(fn (Opportunity $opportunity) => $opportunity->amount * ($opportunity->probability / 100));
        $committedForecast = (float) $openOpportunities
            ->filter(fn (Opportunity $opportunity) => $opportunity->probability >= 70)
            ->sum('amount');

        return response()->json([
            'summary' => [
                'pipelineValue' => round($pipelineValue, 2),
                'weightedForecast' => round($weightedForecast, 2),
                'committedForecast' => round($committedForecast, 2),
                'closedWonValue' => round((float) $opportunities->where('stage', 'closed_won')->sum('amount'), 2),
            ],
            'byStage' => collect(self::STAGES)->map(fn (string $stage) => [
                'stage' => $stage,
                'count' => $opportunities->filter(fn (Opportunity $opportunity) => $this->normalizeStage($opportunity->stage) === $stage)->count(),
                'amount' => round((float) $opportunities->filter(fn (Opportunity $opportunity) => $this->normalizeStage($opportunity->stage) === $stage)->sum('amount'), 2),
                'weightedAmount' => round((float) $opportunities
                    ->filter(fn (Opportunity $opportunity) => $this->normalizeStage($opportunity->stage) === $stage)
                    ->sum(fn (Opportunity $opportunity) => $opportunity->amount * ($opportunity->probability / 100)), 2),
            ])->values(),
            'openDeals' => $openOpportunities->map(fn (Opportunity $opportunity) => [
                'id' => $opportunity->id,
                'code' => $opportunity->code,
                'name' => $opportunity->name,
                'stage' => $opportunity->stage,
                'amount' => (float) $opportunity->amount,
                'probability' => $opportunity->probability,
                'expectedCloseDate' => optional($opportunity->expected_close_date)->toDateString(),
                'lead' => $opportunity->lead ? [
                    'fullName' => $opportunity->lead->full_name,
                    'company' => $opportunity->lead->company,
                ] : null,
            ])->values(),
            'snapshots' => Forecast::query()
                ->latest('snapshot_date')
                ->get()
                ->map(fn (Forecast $forecast) => $this->transformForecast($forecast))
                ->values(),
            'placeholder' => [
                'forecastEngine' => 'Forecast currently uses weighted opportunity amount by stage probability. Seasonality, rep capacity, and scenario modeling can be added next sprint.',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodLabel' => ['required', 'string', 'max:80'],
            'snapshotDate' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
        ]);

        $closedWonOpportunities = Opportunity::query()
            ->where('stage', 'closed_won')
            ->get();

        $forecastAmount = round((float) $closedWonOpportunities->sum('amount'), 2);
        $weightedAmount = round((float) Opportunity::query()
            ->get()
            ->sum(fn (Opportunity $opportunity) => $opportunity->amount * ($opportunity->probability / 100)), 2);

        $forecast = Forecast::query()->create([
            'period_label' => $validated['periodLabel'],
            'snapshot_date' => $validated['snapshotDate'] ?? now()->toDateString(),
            'forecast_amount' => $forecastAmount,
            'weighted_amount' => $weightedAmount,
            'committed_amount' => $forecastAmount,
            'status' => $validated['status'] ?? 'draft',
            'notes' => $validated['notes'] ?? 'Forecast snapshot generated from closed won opportunities.',
            'metadata' => [
                'placeholder' => 'Forecast snapshot uses closed won opportunity totals for the current implementation.',
            ],
        ]);

        return response()->json([
            'message' => 'Forecast snapshot created successfully.',
            'data' => $this->transformForecast($forecast),
        ], 201);
    }

    private function transformForecast(Forecast $forecast): array
    {
        return [
            'id' => $forecast->id,
            'periodLabel' => $forecast->period_label,
            'period_label' => $forecast->period_label,
            'snapshotDate' => optional($forecast->snapshot_date)->toDateString(),
            'forecastAmount' => (float) $forecast->forecast_amount,
            'forecast_amount' => (float) $forecast->forecast_amount,
            'weightedAmount' => (float) $forecast->weighted_amount,
            'committedAmount' => (float) $forecast->committed_amount,
            'status' => $forecast->status,
            'notes' => $forecast->notes,
        ];
    }

    private function normalizeStage(string $stage): string
    {
        return $stage === 'prospecting' ? 'new' : $stage;
    }
}