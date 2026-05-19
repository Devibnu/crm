<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WinLostAnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'status' => in_array($request->query('status', 'all'), ['all', 'won', 'lost'], true)
                ? $request->query('status', 'all')
                : 'all',
            'assigned_to' => trim((string) $request->query('assigned_to', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $opportunities = $this->opportunityQuery($filters)
            ->orderByRaw('expected_close_date IS NULL')
            ->orderByDesc('expected_close_date')
            ->latest('id')
            ->get();

        $quotations = $this->quotationQuery($filters)
            ->with(['customer:id,name', 'opportunity:id,title,assigned_to'])
            ->orderByRaw('COALESCE(valid_until, issued_at) IS NULL')
            ->orderByRaw('COALESCE(valid_until, issued_at) DESC')
            ->latest('id')
            ->get();

        $opportunitySummary = $this->opportunitySummary($opportunities);
        $quotationSummary = $this->quotationSummary($quotations);

        return view('admin.sales.win-loss', [
            'filters' => $filters,
            'assignedToOptions' => Opportunity::query()
                ->whereNotNull('assigned_to')
                ->where('assigned_to', '!=', '')
                ->distinct()
                ->orderBy('assigned_to')
                ->pluck('assigned_to'),
            'opportunities' => $opportunities,
            'quotations' => $quotations,
            'summary' => array_merge($opportunitySummary, $quotationSummary),
            'assignedBreakdown' => $this->assignedBreakdown($opportunities),
            'statusBreakdown' => $this->statusBreakdown($opportunities),
            'monthlyBreakdown' => $this->monthlyBreakdown($opportunities),
        ]);
    }

    /**
     * @param array<string, string> $filters
     */
    protected function opportunityQuery(array $filters): Builder
    {
        return Opportunity::query()
            ->whereIn('status', ['won', 'lost'])
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['assigned_to'] !== '', fn (Builder $query) => $query->where('assigned_to', $filters['assigned_to']))
            ->when($filters['date_from'] !== '', fn (Builder $query) => $query->whereDate('expected_close_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $query) => $query->whereDate('expected_close_date', '<=', $filters['date_to']));
    }

    /**
     * @param array<string, string> $filters
     */
    protected function quotationQuery(array $filters): Builder
    {
        return Quotation::query()
            ->whereIn('status', ['accepted', 'rejected', 'expired'])
            ->when($filters['status'] === 'won', fn (Builder $query) => $query->where('status', 'accepted'))
            ->when($filters['status'] === 'lost', fn (Builder $query) => $query->whereIn('status', ['rejected', 'expired']))
            ->when($filters['assigned_to'] !== '', function (Builder $query) use ($filters) {
                $query->whereHas('opportunity', fn (Builder $opportunityQuery) => $opportunityQuery->where('assigned_to', $filters['assigned_to']));
            })
            ->when($filters['date_from'] !== '', function (Builder $query) use ($filters) {
                $query->where(function (Builder $dateQuery) use ($filters) {
                    $dateQuery
                        ->whereDate('valid_until', '>=', $filters['date_from'])
                        ->orWhere(function (Builder $issuedQuery) use ($filters) {
                            $issuedQuery->whereNull('valid_until')->whereDate('issued_at', '>=', $filters['date_from']);
                        });
                });
            })
            ->when($filters['date_to'] !== '', function (Builder $query) use ($filters) {
                $query->where(function (Builder $dateQuery) use ($filters) {
                    $dateQuery
                        ->whereDate('valid_until', '<=', $filters['date_to'])
                        ->orWhere(function (Builder $issuedQuery) use ($filters) {
                            $issuedQuery->whereNull('valid_until')->whereDate('issued_at', '<=', $filters['date_to']);
                        });
                });
            });
    }

    protected function opportunitySummary(Collection $opportunities): array
    {
        $won = $opportunities->where('status', 'won');
        $lost = $opportunities->where('status', 'lost');
        $closedCount = $won->count() + $lost->count();

        return [
            'total_won_count' => $won->count(),
            'total_lost_count' => $lost->count(),
            'total_won_value' => $this->sumEstimatedValue($won),
            'total_lost_value' => $this->sumEstimatedValue($lost),
            'win_rate' => $closedCount > 0 ? round(($won->count() / $closedCount) * 100, 2) : 0,
            'lost_rate' => $closedCount > 0 ? round(($lost->count() / $closedCount) * 100, 2) : 0,
            'average_won_value' => $won->count() > 0 ? round($this->sumEstimatedValue($won) / $won->count(), 2) : 0,
            'average_lost_value' => $lost->count() > 0 ? round($this->sumEstimatedValue($lost) / $lost->count(), 2) : 0,
        ];
    }

    protected function quotationSummary(Collection $quotations): array
    {
        $accepted = $quotations->where('status', 'accepted');
        $rejected = $quotations->where('status', 'rejected');
        $expired = $quotations->where('status', 'expired');
        $finalCount = $accepted->count() + $rejected->count() + $expired->count();

        return [
            'accepted_count' => $accepted->count(),
            'rejected_count' => $rejected->count(),
            'expired_count' => $expired->count(),
            'accepted_value' => $this->sumAmount($accepted),
            'rejected_value' => $this->sumAmount($rejected),
            'expired_value' => $this->sumAmount($expired),
            'quote_acceptance_rate' => $finalCount > 0 ? round(($accepted->count() / $finalCount) * 100, 2) : 0,
        ];
    }

    protected function assignedBreakdown(Collection $opportunities): Collection
    {
        return $opportunities
            ->groupBy(fn (Opportunity $opportunity) => $opportunity->assigned_to ?: 'Unassigned')
            ->map(function (Collection $items, string $assignedTo): array {
                $won = $items->where('status', 'won');
                $lost = $items->where('status', 'lost');
                $total = $won->count() + $lost->count();

                return [
                    'assigned_to' => $assignedTo,
                    'won_count' => $won->count(),
                    'lost_count' => $lost->count(),
                    'won_value' => $this->sumEstimatedValue($won),
                    'lost_value' => $this->sumEstimatedValue($lost),
                    'win_rate' => $total > 0 ? round(($won->count() / $total) * 100, 2) : 0,
                ];
            })
            ->sortBy('assigned_to')
            ->values();
    }

    protected function statusBreakdown(Collection $opportunities): Collection
    {
        return $opportunities
            ->groupBy('status')
            ->map(fn (Collection $items, string $status): array => [
                'status' => $status,
                'count' => $items->count(),
                'value' => $this->sumEstimatedValue($items),
            ])
            ->sortBy('status')
            ->values();
    }

    protected function monthlyBreakdown(Collection $opportunities): Collection
    {
        return $opportunities
            ->groupBy(fn (Opportunity $opportunity) => $opportunity->expected_close_date?->format('Y-m') ?: 'No Date')
            ->map(function (Collection $items, string $month): array {
                $won = $items->where('status', 'won');
                $lost = $items->where('status', 'lost');

                return [
                    'month' => $month === 'No Date'
                        ? $month
                        : $items->first()->expected_close_date->format('M Y'),
                    'sort_key' => $month,
                    'won_count' => $won->count(),
                    'lost_count' => $lost->count(),
                    'won_value' => $this->sumEstimatedValue($won),
                    'lost_value' => $this->sumEstimatedValue($lost),
                ];
            })
            ->sortByDesc('sort_key')
            ->values();
    }

    protected function sumEstimatedValue(Collection $opportunities): float
    {
        return (float) $opportunities->sum(fn (Opportunity $opportunity) => (float) $opportunity->estimated_value);
    }

    protected function sumAmount(Collection $quotations): float
    {
        return (float) $quotations->sum(fn (Quotation $quotation) => (float) $quotation->amount);
    }
}
