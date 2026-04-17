<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    private const STAGES = [
        'new',
        'qualified',
        'proposal',
        'negotiation',
        'closed_won',
        'closed_lost',
    ];

    private const ACCEPTED_STAGES = [
        'new',
        'qualified',
        'proposal',
        'negotiation',
        'closed_won',
        'closed_lost',
        'prospecting',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Opportunity::query()
            ->with(['lead:id,code,full_name,company,status', 'assignedUser:id,full_name,email,role'])
            ->withCount('quotations');

        if ($stage = $request->string('stage')->toString()) {
            $normalizedStage = $this->normalizeStage($stage);

            if ($normalizedStage === 'new') {
                $query->whereIn('stage', ['new', 'prospecting']);
            }
            else {
                $query->where('stage', $normalizedStage);
            }
        }

        if ($assignedUserId = $request->integer('assignedUserId')) {
            $query->where('assigned_user_id', $assignedUserId);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn ($leadQuery) => $leadQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%"));
            });
        }

        $opportunities = $query->latest()->get();

        return response()->json([
            'summary' => [
                'pipelineValue' => round((float) $opportunities->sum('amount'), 2),
                'weightedForecast' => round((float) $opportunities->sum(fn (Opportunity $opportunity) => $opportunity->amount * ($opportunity->probability / 100)), 2),
                'closedWonValue' => round((float) $opportunities->where('stage', 'closed_won')->sum('amount'), 2),
                'totalOpenDeals' => $opportunities->whereNotIn('stage', ['closed_won', 'closed_lost'])->count(),
            ],
            'salesUsers' => $this->salesUsers(),
            'qualifiedLeads' => Lead::query()
                ->select('id', 'code', 'full_name', 'company', 'status')
                ->where('status', 'qualified')
                ->orderBy('full_name')
                ->get()
                ->map(fn (Lead $lead) => [
                    'id' => $lead->id,
                    'code' => $lead->code,
                    'fullName' => $lead->full_name,
                    'company' => $lead->company,
                    'status' => $lead->status,
                ])
                ->values(),
                'board' => collect(self::STAGES)->map(fn (string $stage) => [
                'stage' => $stage,
                'items' => $opportunities
                    ->filter(fn (Opportunity $opportunity) => $this->normalizeStage($opportunity->stage) === $stage)
                    ->values()
                    ->map(fn (Opportunity $opportunity) => $this->transformOpportunity($opportunity))
                    ->values(),
            ])->values(),
            'data' => $opportunities->map(fn (Opportunity $opportunity) => $this->transformOpportunity($opportunity))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leadId' => ['required', 'integer', 'exists:leads,id'],
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:160'],
            'stage' => ['nullable', 'string', 'in:' . implode(',', self::ACCEPTED_STAGES)],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expectedCloseDate' => ['nullable', 'date'],
            'statusNotes' => ['nullable', 'string'],
        ]);

        $stage = $this->normalizeStage($validated['stage'] ?? 'new');

        $opportunity = Opportunity::query()->create([
            'lead_id' => $validated['leadId'],
            'assigned_user_id' => $validated['assignedUserId'] ?? null,
            'name' => $validated['name'],
            'stage' => $stage,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'IDR',
            'probability' => $validated['probability'] ?? $this->defaultProbability($stage),
            'expected_close_date' => $validated['expectedCloseDate'] ?? null,
            'status_notes' => $validated['statusNotes'] ?? null,
            'closed_at' => in_array($stage, ['closed_won', 'closed_lost'], true) ? now() : null,
            'metadata' => [
                'placeholder' => 'Opportunity board can be enriched with automation, reminders, and task orchestration next sprint.',
            ],
        ]);

        $opportunity->forceFill([
            'code' => sprintf('OPP-%06d', $opportunity->id),
        ])->save();

        return response()->json([
            'message' => 'Opportunity created successfully.',
            'data' => $this->transformOpportunity($opportunity->fresh(['lead', 'assignedUser'])->loadCount('quotations')),
        ], 201);
    }

    public function update(Request $request, Opportunity $opportunity): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['sometimes', 'string', 'in:' . implode(',', self::ACCEPTED_STAGES)],
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expectedCloseDate' => ['nullable', 'date'],
            'statusNotes' => ['nullable', 'string'],
        ]);

        if (array_key_exists('stage', $validated)) {
            $normalizedStage = $this->normalizeStage($validated['stage']);

            $opportunity->stage = $normalizedStage;
            $opportunity->closed_at = in_array($normalizedStage, ['closed_won', 'closed_lost'], true) ? now() : null;

            if (! array_key_exists('probability', $validated)) {
                $opportunity->probability = $this->defaultProbability($normalizedStage);
            }
        }

        if (array_key_exists('assignedUserId', $validated)) {
            $opportunity->assigned_user_id = $validated['assignedUserId'];
        }

        if (array_key_exists('amount', $validated)) {
            $opportunity->amount = $validated['amount'];
        }

        if (array_key_exists('probability', $validated)) {
            $opportunity->probability = $validated['probability'];
        }

        if (array_key_exists('expectedCloseDate', $validated)) {
            $opportunity->expected_close_date = $validated['expectedCloseDate'];
        }

        if (array_key_exists('statusNotes', $validated)) {
            $opportunity->status_notes = $validated['statusNotes'];
        }

        $opportunity->save();

        return response()->json([
            'message' => 'Opportunity updated successfully.',
            'data' => $this->transformOpportunity($opportunity->fresh(['lead', 'assignedUser'])->loadCount('quotations')),
        ]);
    }

    public function updateStage(Request $request, Opportunity $opportunity): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:' . implode(',', self::ACCEPTED_STAGES)],
        ]);

        $normalizedStage = $this->normalizeStage($validated['stage']);

        $opportunity->stage = $normalizedStage;
        $opportunity->closed_at = in_array($normalizedStage, ['closed_won', 'closed_lost'], true) ? now() : null;
        $opportunity->probability = $this->defaultProbability($normalizedStage);
        $opportunity->save();

        return response()->json([
            'message' => 'Opportunity stage updated successfully.',
            'data' => $this->transformOpportunity($opportunity->fresh(['lead', 'assignedUser'])->loadCount('quotations')),
        ]);
    }

    public function destroy(Opportunity $opportunity): JsonResponse
    {
        $opportunity->delete();

        return response()->json([
            'message' => 'Opportunity deleted successfully.',
        ]);
    }

    private function defaultProbability(string $stage): int
    {
        return match ($stage) {
            'new' => 10,
            'qualified' => 25,
            'proposal' => 50,
            'negotiation' => 75,
            'closed_won' => 100,
            'closed_lost' => 0,
            default => 10,
        };
    }

    private function normalizeStage(string $stage): string
    {
        return $stage === 'prospecting' ? 'new' : $stage;
    }

    private function salesUsers(): array
    {
        return User::query()
            ->select('id', 'full_name', 'email', 'role')
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'fullName' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
            ])
            ->values()
            ->all();
    }

    private function transformOpportunity(Opportunity $opportunity): array
    {
        return [
            'id' => $opportunity->id,
            'code' => $opportunity->code,
            'leadId' => $opportunity->lead_id,
            'lead_id' => $opportunity->lead_id,
            'name' => $opportunity->name,
            'title' => $opportunity->name,
            'stage' => $this->normalizeStage($opportunity->stage),
            'amount' => (float) $opportunity->amount,
            'currency' => $opportunity->currency,
            'probability' => $opportunity->probability,
            'expectedCloseDate' => optional($opportunity->expected_close_date)->toDateString(),
            'statusNotes' => $opportunity->status_notes,
            'closedAt' => optional($opportunity->closed_at)->toIso8601String(),
            'quotationsCount' => $opportunity->quotations_count ?? $opportunity->quotations()->count(),
            'lead' => $opportunity->lead ? [
                'id' => $opportunity->lead->id,
                'code' => $opportunity->lead->code,
                'fullName' => $opportunity->lead->full_name,
                'company' => $opportunity->lead->company,
                'status' => $opportunity->lead->status,
            ] : null,
            'assignedUser' => $opportunity->assignedUser ? [
                'id' => $opportunity->assignedUser->id,
                'fullName' => $opportunity->assignedUser->full_name,
                'email' => $opportunity->assignedUser->email,
                'role' => $opportunity->assignedUser->role,
            ] : null,
            'metadata' => $opportunity->metadata,
        ];
    }
}