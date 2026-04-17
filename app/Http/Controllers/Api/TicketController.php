<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\SLA;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Services\SlaTimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function __construct(private readonly SlaTimerService $slaTimerService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmTickets');

        $query = Ticket::query()
            ->with([
                'customer:id,nama,email,status,source',
                'assignedUser:id,full_name,email,role',
                'slaDefinition',
                'activities.user:id,full_name,email',
            ]);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        if ($alertState = $request->string('alertState')->toString()) {
            $query->where('alert_state', $alertState);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('nama', 'like', "%{$search}%"));
            });
        }

        $tickets = $query
            ->latest()
            ->get()
            ->map(fn (Ticket $ticket) => $this->slaTimerService->syncTicket($ticket))
            ->values();

        return response()->json([
            'data' => $tickets->map(fn (Ticket $ticket) => $this->transformTicket($ticket))->values(),
            'meta' => [
                'counts' => [
                    'open' => $tickets->where('status', 'open')->count(),
                    'inProgress' => $tickets->where('status', 'in_progress')->count(),
                    'resolved' => $tickets->where('status', 'resolved')->count(),
                    'overdue' => $tickets->where('alert_state', 'overdue')->count(),
                ],
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmTickets');

        return response()->json([
            'customers' => Pelanggan::query()
                ->select('id', 'nama', 'email', 'status', 'source')
                ->orderBy('nama')
                ->get()
                ->map(fn (Pelanggan $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->nama,
                    'email' => $customer->email,
                    'status' => $customer->status,
                    'source' => $customer->source,
                ])->values(),
            'agents' => User::query()
                ->select('id', 'full_name', 'email', 'role', 'status')
                ->where('status', 'active')
                ->orderBy('full_name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                ])->values(),
            'slaDefinitions' => SLA::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (SLA $sla) => $this->transformSlaDefinition($sla))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'create', 'CrmTickets');

        $validated = $request->validate([
            'customerId' => ['required', 'integer', 'exists:pelanggan,id'],
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
            'slaDefinitionId' => ['nullable', 'integer', 'exists:sla_definitions,id'],
            'category' => ['required', 'string', 'in:general,technical,billing,priority-follow-up'],
            'priority' => ['required', 'string', 'in:low,medium,high,critical'],
            'subject' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string'],
        ]);

        $ticket = DB::transaction(function () use ($request, $validated): Ticket {
            $ticket = Ticket::query()->create([
                'customer_id' => $validated['customerId'],
                'assigned_user_id' => $validated['assignedUserId'] ?? null,
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'status' => 'open',
                'priority' => $validated['priority'],
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
                'last_activity_at' => now(),
            ]);

            $ticket->forceFill([
                'code' => sprintf('TCK-%06d', $ticket->id),
            ])->save();

            $slaDefinition = ! empty($validated['slaDefinitionId'])
                ? SLA::query()->find($validated['slaDefinitionId'])
                : null;

            $ticket = $this->slaTimerService->applyDefinition($ticket, $slaDefinition);

            TicketActivity::record(
                $ticket,
                'ticket_created',
                $request->user(),
                'Ticket created',
                'Initial ticket record created from the service management form.',
                [
                    'category' => $ticket->category,
                    'priority' => $ticket->priority,
                ],
            );

            TicketActivity::record(
                $ticket,
                'assignment_recorded',
                $request->user(),
                'Assignment placeholder recorded',
                $ticket->assigned_user_id ? 'Ticket was assigned during creation.' : 'Ticket created without assignee. Assignment can be completed later.',
                [
                    'assignedUserId' => $ticket->assigned_user_id,
                ],
            );

            return $this->slaTimerService->syncTicket($ticket->fresh([
                'customer',
                'assignedUser',
                'slaDefinition',
                'activities.user',
            ]));
        });

        return response()->json([
            'message' => 'Ticket created successfully.',
            'data' => $this->transformTicket($ticket),
        ], 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'CrmTickets');

        $ticket = $this->slaTimerService->syncTicket($ticket->load([
            'customer',
            'assignedUser',
            'slaDefinition',
            'activities.user',
        ]));

        return response()->json([
            'data' => $this->transformTicket($ticket),
        ]);
    }

    public function assign(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmTickets');

        $validated = $request->validate([
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $previousAssignee = $ticket->assignedUser;
        $nextAssignee = ! empty($validated['assignedUserId'])
            ? User::query()->findOrFail($validated['assignedUserId'])
            : null;

        $ticket->forceFill([
            'assigned_user_id' => $nextAssignee?->id,
            'updated_by' => $request->user()?->id,
        ])->save();

        TicketActivity::record(
            $ticket,
            'assignment_changed',
            $request->user(),
            'Ticket assignment updated',
            sprintf(
                'Assignee changed from %s to %s.',
                $previousAssignee?->full_name ?? 'Unassigned',
                $nextAssignee?->full_name ?? 'Unassigned',
            ),
            [
                'fromUserId' => $previousAssignee?->id,
                'toUserId' => $nextAssignee?->id,
            ],
        );

        $ticket = $this->slaTimerService->syncTicket($ticket->fresh([
            'customer',
            'assignedUser',
            'slaDefinition',
            'activities.user',
        ]));

        return response()->json([
            'message' => 'Ticket assignment updated successfully.',
            'data' => $this->transformTicket($ticket),
        ]);
    }

    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmTickets');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved'],
        ]);

        $previousStatus = $ticket->status;

        $ticket->forceFill([
            'status' => $validated['status'],
            'updated_by' => $request->user()?->id,
            'first_responded_at' => $validated['status'] === 'in_progress' && ! $ticket->first_responded_at ? now() : $ticket->first_responded_at,
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ])->save();

        TicketActivity::record(
            $ticket,
            'status_changed',
            $request->user(),
            'Ticket status updated',
            sprintf('Status changed from %s to %s.', $previousStatus, $validated['status']),
            [
                'from' => $previousStatus,
                'to' => $validated['status'],
            ],
        );

        $ticket = $this->slaTimerService->syncTicket($ticket->fresh([
            'customer',
            'assignedUser',
            'slaDefinition',
            'activities.user',
        ]));

        return response()->json([
            'message' => 'Ticket status updated successfully.',
            'data' => $this->transformTicket($ticket),
        ]);
    }

    public function escalate(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'CrmTickets');

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'targetPriority' => ['nullable', 'string', 'in:high,critical'],
        ]);

        $targetPriority = $validated['targetPriority'] ?? match ($ticket->priority) {
            'low' => 'medium',
            'medium' => 'high',
            default => 'critical',
        };

        $fromPriority = $ticket->priority;

        $ticket->forceFill([
            'priority' => $targetPriority,
            'escalation_level' => $ticket->escalation_level + 1,
            'updated_by' => $request->user()?->id,
        ])->save();

        TicketActivity::record(
            $ticket,
            'manual_escalation_placeholder',
            $request->user(),
            'Manual escalation placeholder triggered',
            $validated['reason'] ?? 'Manual escalation captured. Cross-team workflow can be connected in the next sprint.',
            [
                'fromPriority' => $fromPriority,
                'toPriority' => $targetPriority,
            ],
        );

        $ticket = $this->slaTimerService->syncTicket($ticket->fresh([
            'customer',
            'assignedUser',
            'slaDefinition',
            'activities.user',
        ]));

        return response()->json([
            'message' => 'Ticket escalation placeholder executed.',
            'data' => $this->transformTicket($ticket),
        ]);
    }

    private function transformTicket(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'code' => $ticket->code,
            'title' => $ticket->subject,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'category' => $ticket->category,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'assigned_to' => $ticket->assigned_user_id,
            'sla_id' => $ticket->sla_definition_id,
            'escalationLevel' => $ticket->escalation_level,
            'alertState' => $ticket->alert_state,
            'alerts' => $this->slaTimerService->buildAlertCodes($ticket),
            'firstResponseDueAt' => optional($ticket->first_response_due_at)->toIso8601String(),
            'resolutionDueAt' => optional($ticket->resolution_due_at)->toIso8601String(),
            'firstRespondedAt' => optional($ticket->first_responded_at)->toIso8601String(),
            'resolvedAt' => optional($ticket->resolved_at)->toIso8601String(),
            'lastActivityAt' => optional($ticket->last_activity_at)->toIso8601String(),
            'customer' => $ticket->customer ? [
                'id' => $ticket->customer->id,
                'name' => $ticket->customer->nama,
                'email' => $ticket->customer->email,
                'status' => $ticket->customer->status,
                'source' => $ticket->customer->source,
            ] : null,
            'assignedUser' => $ticket->assignedUser ? [
                'id' => $ticket->assignedUser->id,
                'fullName' => $ticket->assignedUser->full_name,
                'email' => $ticket->assignedUser->email,
                'role' => $ticket->assignedUser->role,
            ] : null,
            'slaDefinition' => $ticket->slaDefinition ? $this->transformSlaDefinition($ticket->slaDefinition) : null,
            'activities' => $ticket->activities->map(fn (TicketActivity $activity) => [
                'id' => $activity->id,
                'activityType' => $activity->activity_type,
                'title' => $activity->title,
                'description' => $activity->description,
                'createdAt' => optional($activity->created_at)->toIso8601String(),
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'fullName' => $activity->user->full_name,
                    'email' => $activity->user->email,
                ] : null,
            ])->values(),
        ];
    }

    private function transformSlaDefinition(SLA $sla): array
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