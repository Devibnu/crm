<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateSlaPolicyRequest;
use App\Http\Requests\Admin\UpdateSlaPolicyRequest;
use App\Models\BusinessCalendar;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketSlaEscalation;
use App\Services\Sla\SlaPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaPolicyController extends Controller
{
    public function __construct(
        protected SlaPolicyService $slaPolicyService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $priority = trim((string) $request->query('priority', ''));
        $active = trim((string) $request->query('is_active', ''));

        $policies = SlaPolicy::query()
            ->with('businessCalendar:id,name,timezone,is_default,is_active')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filterPriority($priority, SlaPolicy::priorityOptions())
            ->filterActive($active)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $averageResponse = (float) SlaPolicy::query()->avg('response_time_minutes');
        $averageResolution = (float) SlaPolicy::query()->avg('resolution_time_minutes');
        $activeTicketStatuses = ['open', 'in_progress', 'waiting_customer', 'reopened'];
        $warningTypes = [
            TicketSlaEscalation::TYPE_RESPONSE_WARNING,
            TicketSlaEscalation::TYPE_RESOLUTION_WARNING,
        ];
        $breachTypes = [
            TicketSlaEscalation::TYPE_RESPONSE_BREACH,
            TicketSlaEscalation::TYPE_RESOLUTION_BREACH,
        ];

        $summary = [
            'total' => SlaPolicy::query()->count(),
            'active' => SlaPolicy::query()->where('is_active', true)->count(),
            'high_urgent' => SlaPolicy::query()->whereIn('priority', ['high', 'urgent'])->count(),
            'tickets_on_time' => Ticket::query()
                ->whereIn('status', $activeTicketStatuses)
                ->whereNotNull('sla_policy_id')
                ->whereDoesntHave('slaEscalations', fn ($query) => $query->whereIn('type', array_merge($warningTypes, $breachTypes)))
                ->count(),
            'warning' => TicketSlaEscalation::query()
                ->whereIn('type', $warningTypes)
                ->whereHas('ticket', fn ($query) => $query->whereIn('status', $activeTicketStatuses))
                ->distinct('ticket_id')
                ->count('ticket_id'),
            'breached' => TicketSlaEscalation::query()
                ->whereIn('type', $breachTypes)
                ->whereHas('ticket', fn ($query) => $query->whereIn('status', $activeTicketStatuses))
                ->distinct('ticket_id')
                ->count('ticket_id'),
            'average_response' => $averageResponse,
            'average_resolution' => $averageResolution,
        ];

        return view('admin.service.sla.index', [
            'policies' => $policies,
            'search' => $search,
            'selectedPriority' => $priority,
            'selectedActive' => $active,
            'priorityOptions' => SlaPolicy::priorityOptions(),
            'activeOptions' => SlaPolicy::activeOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.sla.create', [
            'policy' => null,
            'priorityOptions' => SlaPolicy::priorityOptions(),
            'businessCalendars' => $this->activeBusinessCalendars(),
        ]);
    }

    public function store(CreateSlaPolicyRequest $request): RedirectResponse
    {
        $policy = $this->slaPolicyService->create($request->policyData());

        return redirect()
            ->route('admin.service.sla.show', $policy)
            ->with('success', 'SLA policy berhasil ditambahkan.');
    }

    public function show(SlaPolicy $sla): View
    {
        return view('admin.service.sla.show', [
            'policy' => $sla->load('businessCalendar.workingHours'),
        ]);
    }

    public function edit(SlaPolicy $sla): View
    {
        return view('admin.service.sla.edit', [
            'policy' => $sla->load('businessCalendar'),
            'priorityOptions' => SlaPolicy::priorityOptions(),
            'businessCalendars' => $this->activeBusinessCalendars(),
        ]);
    }

    public function update(UpdateSlaPolicyRequest $request, SlaPolicy $sla): RedirectResponse
    {
        $this->slaPolicyService->update($sla, $request->policyData());

        return redirect()
            ->route('admin.service.sla.show', $sla)
            ->with('success', 'SLA policy berhasil diperbarui.');
    }

    public function destroy(SlaPolicy $sla): RedirectResponse
    {
        $this->slaPolicyService->delete($sla);

        return redirect()
            ->route('admin.service.sla.index')
            ->with('success', 'SLA policy berhasil dihapus.');
    }

    protected function activeBusinessCalendars()
    {
        return BusinessCalendar::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'timezone', 'is_default', 'is_active']);
    }

}
