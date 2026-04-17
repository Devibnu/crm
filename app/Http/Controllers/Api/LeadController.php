<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::query()
            ->with(['assignedUser:id,full_name,email,role', 'capturedBy:id,full_name,email'])
            ->withCount('opportunities');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($assignedUserId = $request->integer('assignedUserId')) {
            $query->where('assigned_user_id', $assignedUserId);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $leads = $query->latest()->get();

        return response()->json([
            'summary' => [
                'total' => $leads->count(),
                'new' => $leads->where('status', 'new')->count(),
                'qualified' => $leads->where('status', 'qualified')->count(),
                'disqualified' => $leads->where('status', 'disqualified')->count(),
            ],
            'salesUsers' => $this->salesUsers(),
            'data' => $leads->map(fn (Lead $lead) => $this->transformLead($lead))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:160'],
            'source' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'string', 'in:new,qualified,disqualified'],
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
            'qualificationNotes' => ['nullable', 'string'],
        ]);

        $status = $validated['status'] ?? 'new';

        $lead = Lead::query()->create([
            'full_name' => $validated['fullName'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'source' => $validated['source'] ?? 'manual',
            'status' => $status,
            'assigned_user_id' => $validated['assignedUserId'] ?? null,
            'captured_by' => $request->user()?->id,
            'qualification_notes' => $validated['qualificationNotes'] ?? null,
            'qualified_at' => $status === 'qualified' ? now() : null,
            'disqualified_at' => $status === 'disqualified' ? now() : null,
            'metadata' => [
                'placeholder' => 'Lead capture is stored and ready for deeper enrichment in the next sprint.',
            ],
        ]);

        $lead->forceFill([
            'code' => sprintf('LED-%06d', $lead->id),
        ])->save();

        return response()->json([
            'message' => 'Lead created successfully.',
            'data' => $this->transformLead($lead->fresh(['assignedUser', 'capturedBy'])->loadCount('opportunities')),
        ], 201);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:new,qualified,disqualified'],
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
            'qualificationNotes' => ['nullable', 'string'],
            'lastContactedAt' => ['nullable', 'date'],
        ]);

        if (array_key_exists('status', $validated)) {
            $lead->status = $validated['status'];
            $lead->qualified_at = $validated['status'] === 'qualified' ? now() : null;
            $lead->disqualified_at = $validated['status'] === 'disqualified' ? now() : null;
        }

        if (array_key_exists('assignedUserId', $validated)) {
            $lead->assigned_user_id = $validated['assignedUserId'];
        }

        if (array_key_exists('qualificationNotes', $validated)) {
            $lead->qualification_notes = $validated['qualificationNotes'];
        }

        if (array_key_exists('lastContactedAt', $validated)) {
            $lead->last_contacted_at = $validated['lastContactedAt'];
        }

        $lead->save();

        return response()->json([
            'message' => 'Lead updated successfully.',
            'data' => $this->transformLead($lead->fresh(['assignedUser', 'capturedBy'])->loadCount('opportunities')),
        ]);
    }

    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'assignedUserId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $lead->assigned_user_id = $validated['assignedUserId'] ?? null;
        $lead->save();

        return response()->json([
            'message' => 'Lead assignment updated successfully.',
            'data' => $this->transformLead($lead->fresh(['assignedUser', 'capturedBy'])->loadCount('opportunities')),
        ]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json([
            'message' => 'Lead deleted successfully.',
        ]);
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

    private function transformLead(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'code' => $lead->code,
            'fullName' => $lead->full_name,
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'source' => $lead->source,
            'status' => $lead->status,
            'qualificationNotes' => $lead->qualification_notes,
            'assigned_user_id' => $lead->assigned_user_id,
            'lastContactedAt' => optional($lead->last_contacted_at)->toIso8601String(),
            'qualifiedAt' => optional($lead->qualified_at)->toIso8601String(),
            'disqualifiedAt' => optional($lead->disqualified_at)->toIso8601String(),
            'opportunitiesCount' => $lead->opportunities_count ?? $lead->opportunities()->count(),
            'assignedUser' => $lead->assignedUser ? [
                'id' => $lead->assignedUser->id,
                'fullName' => $lead->assignedUser->full_name,
                'email' => $lead->assignedUser->email,
                'role' => $lead->assignedUser->role,
            ] : null,
            'capturedBy' => $lead->capturedBy ? [
                'id' => $lead->capturedBy->id,
                'fullName' => $lead->capturedBy->full_name,
            ] : null,
            'metadata' => $lead->metadata,
        ];
    }
}