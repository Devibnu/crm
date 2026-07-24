<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseResolution;
use App\Models\KnowledgeBase;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseResolutionController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $search = trim((string) $request->query('q', ''));
        $resolutionType = trim((string) $request->query('resolution_type', ''));
        $outcome = trim((string) $request->query('resolution_outcome', ''));
        $rootCause = trim((string) $request->query('root_cause', ''));
        $knowledgeCandidate = trim((string) $request->query('knowledge_candidate', ''));
        $customerNotified = trim((string) $request->query('customer_notified', ''));
        $dateFrom = $this->dateQuery($request, 'date_from');
        $dateTo = $this->dateQuery($request, 'date_to');

        $query = $this->filteredQuery($search, $resolutionType, $outcome, $rootCause, $knowledgeCandidate, $customerNotified, $dateFrom, $dateTo);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($query);
        }

        $resolutions = (clone $query)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => CaseResolution::query()->count(),
            'resolved' => CaseResolution::query()->where('resolution_outcome', CaseResolution::OUTCOME_RESOLVED)->count(),
            'escalated' => CaseResolution::query()->where('resolution_outcome', CaseResolution::OUTCOME_ESCALATED)->count(),
            'workaround' => CaseResolution::query()->where('resolution_outcome', CaseResolution::OUTCOME_WORKAROUND)->count(),
            'knowledge_candidate' => CaseResolution::query()->where('knowledge_candidate', true)->count(),
            'average_resolution_time' => (int) round((float) CaseResolution::query()->whereNotNull('resolution_duration_minutes')->avg('resolution_duration_minutes')),
            'average_reopen_count' => (float) CaseResolution::query()->avg('reopened_count'),
        ];

        return view('admin.service.case-resolutions.index', [
            'resolutions' => $resolutions,
            'search' => $search,
            'selectedResolutionType' => $resolutionType,
            'selectedResolutionOutcome' => $outcome,
            'selectedRootCause' => $rootCause,
            'selectedKnowledgeCandidate' => $knowledgeCandidate,
            'selectedCustomerNotified' => $customerNotified,
            'selectedDateFrom' => $dateFrom,
            'selectedDateTo' => $dateTo,
            'resolutionTypeOptions' => $this->resolutionTypeOptions(),
            'resolutionOutcomeOptions' => $this->resolutionOutcomeOptions(),
            'rootCauseOptions' => $this->rootCauseOptions(),
            'knowledgeCandidateOptions' => $this->yesNoOptions(),
            'customerNotifiedOptions' => $this->customerNotifiedOptions(),
            'summary' => $summary,
            'analytics' => $this->analytics(),
        ]);
    }

    public function create(): View
    {
        return view('admin.service.case-resolutions.create', [
            'resolution' => null,
            'tickets' => $this->ticketOptions(),
            'knowledgeArticles' => $this->knowledgeArticleOptions(),
            'resolutionTypeOptions' => $this->resolutionTypeOptions(),
            'resolutionOutcomeOptions' => $this->resolutionOutcomeOptions(),
            'rootCauseOptions' => $this->rootCauseOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resolution = CaseResolution::create($this->validatedData($request));

        return redirect()
            ->route('admin.service.case-resolutions.show', $resolution)
            ->with('success', 'Case resolution berhasil ditambahkan.');
    }

    public function show(CaseResolution $caseResolution): View
    {
        return view('admin.service.case-resolutions.show', [
            'resolution' => $caseResolution->load([
                'knowledgeArticle:id,title',
                'ticket.customer:id,name,company_name,email,phone',
                'ticket.lead:id,name,source',
                'ticket.sourceConversation:id,contact_name,phone_number',
                'ticket.slaBusinessCalendar:id,name,timezone',
                'ticket.slaEscalations' => fn ($query) => $query->latest('triggered_at'),
            ]),
        ]);
    }

    public function edit(CaseResolution $caseResolution): View
    {
        return view('admin.service.case-resolutions.edit', [
            'resolution' => $caseResolution->load([
                'ticket.customer:id,name,company_name,email,phone',
                'ticket.slaBusinessCalendar:id,name,timezone',
                'ticket.slaEscalations',
                'knowledgeArticle:id,title',
            ]),
            'tickets' => $this->ticketOptions(),
            'knowledgeArticles' => $this->knowledgeArticleOptions(),
            'resolutionTypeOptions' => $this->resolutionTypeOptions(),
            'resolutionOutcomeOptions' => $this->resolutionOutcomeOptions(),
            'rootCauseOptions' => $this->rootCauseOptions(),
        ]);
    }

    public function update(Request $request, CaseResolution $caseResolution): RedirectResponse
    {
        $caseResolution->update($this->validatedData($request));

        return redirect()
            ->route('admin.service.case-resolutions.show', $caseResolution)
            ->with('success', 'Case resolution berhasil diperbarui.');
    }

    public function destroy(CaseResolution $caseResolution): RedirectResponse
    {
        $caseResolution->delete();

        return redirect()
            ->route('admin.service.case-resolutions.index')
            ->with('success', 'Case resolution berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'ticket_id' => ['required', 'exists:tickets,id'],
            'resolution_summary' => ['required', 'string', 'max:255'],
            'resolution_notes' => ['nullable', 'string'],
            'root_cause' => ['nullable', Rule::in($this->rootCauseOptions())],
            'workaround' => ['nullable', 'string'],
            'permanent_fix' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'resolution_type' => ['required', Rule::in($this->resolutionTypeOptions())],
            'resolution_outcome' => ['required', Rule::in($this->resolutionOutcomeOptions())],
            'resolved_by' => ['nullable', 'string', 'max:255'],
            'resolved_at' => ['nullable', 'date'],
            'customer_notified' => ['required', 'boolean'],
            'customer_notified_at' => ['nullable', 'date'],
            'customer_confirmation_at' => ['nullable', 'date'],
            'resolution_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'knowledge_candidate' => ['required', 'boolean'],
            'knowledge_article_id' => ['nullable', 'exists:knowledge_bases,id'],
        ]);

        if (! isset($validated['resolution_duration_minutes']) && ! empty($validated['resolved_at'])) {
            $ticket = Ticket::query()->find($validated['ticket_id']);
            $resolvedAt = Carbon::parse($validated['resolved_at']);

            if ($ticket?->created_at) {
                $validated['resolution_duration_minutes'] = max(0, $ticket->created_at->diffInMinutes($resolvedAt));
            }
        }

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function resolutionTypeOptions(): array
    {
        return CaseResolution::resolutionTypeOptions();
    }

    /**
     * @return array<int, string>
     */
    protected function resolutionOutcomeOptions(): array
    {
        return CaseResolution::resolutionOutcomeOptions();
    }

    /**
     * @return array<int, string>
     */
    protected function rootCauseOptions(): array
    {
        return CaseResolution::rootCauseOptions();
    }

    /**
     * @return array<string, string>
     */
    protected function customerNotifiedOptions(): array
    {
        return $this->yesNoOptions();
    }

    /**
     * @return array<string, string>
     */
    protected function yesNoOptions(): array
    {
        return [
            'yes' => 'Yes',
            'no' => 'No',
        ];
    }

    protected function filteredQuery(string $search, string $resolutionType, string $outcome, string $rootCause, string $knowledgeCandidate, string $customerNotified, ?string $dateFrom, ?string $dateTo): Builder
    {
        return CaseResolution::query()
            ->with([
                'knowledgeArticle:id,title',
                'ticket:id,ticket_number,subject,customer_id,priority,status,channel,assigned_to,created_at,due_at,sla_policy_id,sla_business_calendar_id,response_due_at,resolution_due_at,sla_response_breached_at,sla_resolution_breached_at,resolved_at,closed_at',
                'ticket.customer:id,name,company_name',
                'ticket.slaBusinessCalendar:id,name,timezone',
                'ticket.slaEscalations',
            ])
            ->when($search !== '', fn (Builder $query) => $query->search($search))
            ->filterResolutionType($resolutionType, $this->resolutionTypeOptions())
            ->filterOutcome($outcome)
            ->filterRootCause($rootCause)
            ->filterKnowledgeCandidate($knowledgeCandidate)
            ->filterCustomerNotified($customerNotified)
            ->filterDateRange($dateFrom, $dateTo);
    }

    /**
     * @return array<string, mixed>
     */
    protected function analytics(): array
    {
        return [
            'types' => $this->topCounts('resolution_type'),
            'root_causes' => $this->topCounts('root_cause'),
            'outcomes' => $this->topCounts('resolution_outcome'),
            'reopened_categories' => CaseResolution::query()
                ->selectRaw('root_cause as label, SUM(reopened_count) as aggregate')
                ->groupBy('root_cause')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->pluck('aggregate', 'label'),
        ];
    }

    protected function topCounts(string $column): mixed
    {
        return CaseResolution::query()
            ->selectRaw("{$column} as label, COUNT(*) as aggregate")
            ->groupBy($column)
            ->orderByDesc('aggregate')
            ->limit(5)
            ->pluck('aggregate', 'label');
    }

    protected function exportCsv(Builder $query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Ticket Number',
                'Subject',
                'Customer',
                'Resolver',
                'Summary',
                'Resolution Type',
                'Outcome',
                'Root Cause',
                'Knowledge Candidate',
                'Customer Notified',
                'Resolved At',
                'Duration Minutes',
                'Reopened Count',
            ]);

            (clone $query)->latest()->chunk(200, function ($resolutions) use ($handle): void {
                foreach ($resolutions as $resolution) {
                    fputcsv($handle, [
                        $resolution->ticket?->ticket_number,
                        $resolution->ticket?->subject,
                        $resolution->ticket?->customer?->name,
                        $resolution->resolved_by,
                        $resolution->resolution_summary,
                        $resolution->resolution_type,
                        $resolution->resolution_outcome,
                        $resolution->root_cause,
                        $resolution->knowledge_candidate ? 'Yes' : 'No',
                        $resolution->customer_notified ? 'Yes' : 'No',
                        $resolution->resolved_at?->format('Y-m-d H:i:s'),
                        $resolution->resolution_duration_minutes,
                        $resolution->reopened_count,
                    ]);
                }
            });

            fclose($handle);
        }, 'case-resolutions.csv', ['Content-Type' => 'text/csv']);
    }

    protected function dateQuery(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    protected function ticketOptions(): mixed
    {
        return Ticket::query()
            ->with([
                'customer:id,name,company_name,email,phone',
                'slaBusinessCalendar:id,name,timezone',
                'slaEscalations',
            ])
            ->latest()
            ->get(['id', 'ticket_number', 'subject', 'customer_id', 'priority', 'status', 'channel', 'assigned_to', 'sla_policy_id', 'sla_business_calendar_id', 'response_due_at', 'resolution_due_at', 'sla_response_breached_at', 'sla_resolution_breached_at']);
    }

    protected function knowledgeArticleOptions(): mixed
    {
        return KnowledgeBase::query()
            ->orderBy('title')
            ->get(['id', 'title']);
    }
}
