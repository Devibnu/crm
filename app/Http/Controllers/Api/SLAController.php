<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SLA;
use App\Models\Ticket;
use App\Services\SlaTimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SLAController extends Controller
{
    public function __construct(private readonly SlaTimerService $slaTimerService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmTickets');

        $definitions = SLA::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $tickets = Ticket::query()
            ->with(['customer:id,nama,email', 'assignedUser:id,full_name,email,role', 'slaDefinition'])
            ->latest()
            ->get()
            ->map(fn (Ticket $ticket) => $this->slaTimerService->syncTicket($ticket))
            ->values();

        $alerts = $tickets
            ->filter(fn (Ticket $ticket) => in_array($ticket->alert_state, ['due_soon', 'overdue'], true))
            ->sortBy('resolution_due_at')
            ->values();

        return response()->json([
            'summary' => [
                'activeDefinitions' => $definitions->where('is_active', true)->count(),
                'dueSoonTickets' => $tickets->where('alert_state', 'due_soon')->count(),
                'overdueTickets' => $tickets->where('alert_state', 'overdue')->count(),
                'resolvedTickets' => $tickets->where('status', 'resolved')->count(),
            ],
            'definitions' => $definitions->map(fn (SLA $sla) => $this->transformDefinition($sla))->values(),
            'alerts' => $alerts->map(fn (Ticket $ticket) => [
                'ticketId' => $ticket->id,
                'ticketCode' => $ticket->code,
                'subject' => $ticket->subject,
                'alertState' => $ticket->alert_state,
                'priority' => $ticket->priority,
                'resolutionDueAt' => optional($ticket->resolution_due_at)->toIso8601String(),
                'customer' => $ticket->customer ? [
                    'id' => $ticket->customer->id,
                    'name' => $ticket->customer->nama,
                    'email' => $ticket->customer->email,
                ] : null,
                'assignedUser' => $ticket->assignedUser ? [
                    'id' => $ticket->assignedUser->id,
                    'fullName' => $ticket->assignedUser->full_name,
                ] : null,
                'alertCodes' => $this->slaTimerService->buildAlertCodes($ticket),
            ])->values(),
            'placeholder' => [
                'alert' => 'Frontend alerts are emitted from alertState and alertCodes. External notification delivery can be added next sprint.',
                'escalation' => 'Automatic and manual escalation currently write activity logs as placeholders.',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'create', 'CrmTickets');

        $validated = $this->prepareDefinitionPayload($request);

        $sla = SLA::query()->create($validated);

        return response()->json([
            'message' => 'SLA definition created successfully.',
            'data' => $this->transformDefinition($sla),
        ], 201);
    }

    public function update(Request $request, SLA $sla): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmTickets');

        $validated = $this->prepareDefinitionPayload($request);

        $sla->update($validated);

        return response()->json([
            'message' => 'SLA definition updated successfully.',
            'data' => $this->transformDefinition($sla->fresh()),
        ]);
    }

    private function prepareDefinitionPayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'in:general,technical,billing,priority-follow-up'],
            'priority' => ['required', 'string', 'in:low,medium,high,critical'],
            'firstResponseMinutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'resolutionMinutes' => ['required', 'integer', 'min:1', 'max:20160'],
            'warningBeforeMinutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'autoEscalate' => ['required', 'boolean'],
            'escalationPriority' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'isActive' => ['required', 'boolean'],
        ]);

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'priority' => $validated['priority'],
            'first_response_minutes' => $validated['firstResponseMinutes'],
            'resolution_minutes' => $validated['resolutionMinutes'],
            'warning_before_minutes' => $validated['warningBeforeMinutes'],
            'auto_escalate' => $validated['autoEscalate'],
            'escalation_priority' => $validated['escalationPriority'] ?? null,
            'is_active' => $validated['isActive'],
        ];
    }

    private function transformDefinition(SLA $sla): array
    {
        return [
            'id' => $sla->id,
            'name' => $sla->name,
            'description' => $sla->description,
            'category' => $sla->category,
            'priority' => $sla->priority,
            'firstResponseMinutes' => $sla->first_response_minutes,
            'resolutionMinutes' => $sla->resolution_minutes,
            'warningBeforeMinutes' => $sla->warning_before_minutes,
            'autoEscalate' => $sla->auto_escalate,
            'escalationPriority' => $sla->escalation_priority,
            'isActive' => $sla->is_active,
        ];
    }
}