<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SalesPipelineController extends Controller
{
    public function index(Request $request): View
    {
        $assignedTo = trim((string) $request->query('assigned_to', ''));
        $dateFrom = trim((string) $request->query('expected_close_date_from', ''));
        $dateTo = trim((string) $request->query('expected_close_date_to', ''));

        $stages = [
            'open' => 'Open',
            'qualified' => 'Qualified',
            'proposal' => 'Proposal',
            'negotiation' => 'Negotiation',
            'won' => 'Won',
            'lost' => 'Lost',
        ];

        $opportunities = Opportunity::query()
            ->when($assignedTo !== '', function ($query) use ($assignedTo) {
                $query->where('assigned_to', 'like', "%{$assignedTo}%");
            })
            ->when($this->isValidDate($dateFrom), function ($query) use ($dateFrom) {
                $query->whereDate('expected_close_date', '>=', $dateFrom);
            })
            ->when($this->isValidDate($dateTo), function ($query) use ($dateTo) {
                $query->whereDate('expected_close_date', '<=', $dateTo);
            })
            ->orderByRaw('expected_close_date IS NULL')
            ->orderBy('expected_close_date')
            ->latest('id')
            ->get();

        $stageRows = [];
        $stageOpportunities = [];

        foreach ($stages as $stageKey => $stageName) {
            $items = $opportunities->where('status', $stageKey)->values();
            $count = $items->count();
            $totalValue = $this->sumEstimatedValue($items);
            $avgProbability = $count > 0 ? (float) $items->avg('probability') : 0.0;
            $weightedValue = $this->sumWeightedValue($items);

            $stageOpportunities[$stageKey] = $items;
            $stageRows[] = [
                'key' => $stageKey,
                'name' => $stageName,
                'count' => $count,
                'total_value' => $totalValue,
                'avg_probability' => $avgProbability,
                'weighted_value' => $weightedValue,
            ];
        }

        $nonLost = $opportunities->where('status', '!=', 'lost');

        $summary = [
            'total_pipeline_value' => $this->sumEstimatedValue($nonLost),
            'weighted_forecast' => $this->sumWeightedValue($nonLost),
            'won_value' => $this->sumEstimatedValue($opportunities->where('status', 'won')),
            'lost_value' => $this->sumEstimatedValue($opportunities->where('status', 'lost')),
            'open_opportunities_count' => $opportunities->where('status', 'open')->count(),
        ];

        return view('admin.sales.pipeline', [
            'stages' => $stages,
            'stageRows' => $stageRows,
            'stageOpportunities' => $stageOpportunities,
            'summary' => $summary,
            'filters' => [
                'assigned_to' => $assignedTo,
                'expected_close_date_from' => $dateFrom,
                'expected_close_date_to' => $dateTo,
            ],
        ]);
    }

    protected function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $parts = explode('-', $value);

        if (count($parts) !== 3) {
            return false;
        }

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    protected function sumEstimatedValue(Collection $items): float
    {
        return (float) $items->sum(function ($item) {
            return (float) $item->estimated_value;
        });
    }

    protected function sumWeightedValue(Collection $items): float
    {
        return (float) $items->sum(function ($item) {
            return ((float) $item->estimated_value) * (((int) $item->probability) / 100);
        });
    }
}
