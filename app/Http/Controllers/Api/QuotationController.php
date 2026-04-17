<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(): JsonResponse
    {
        $quotations = Quotation::query()
            ->with(['opportunity.lead:id,full_name,company'])
            ->latest()
            ->get();

        return response()->json([
            'summary' => [
                'draft' => $quotations->where('status', 'draft')->count(),
                'submitted' => $quotations->where('status', 'submitted')->count(),
                'approved' => $quotations->where('status', 'approved')->count(),
                'rejected' => $quotations->where('status', 'rejected')->count(),
            ],
            'opportunities' => Opportunity::query()
                ->with('lead:id,full_name,company')
                ->orderBy('name')
                ->get()
                ->map(fn (Opportunity $opportunity) => [
                    'id' => $opportunity->id,
                    'code' => $opportunity->code,
                    'name' => $opportunity->name,
                    'stage' => $opportunity->stage,
                    'amount' => (float) $opportunity->amount,
                    'currency' => $opportunity->currency,
                    'lead' => $opportunity->lead ? [
                        'id' => $opportunity->lead->id,
                        'fullName' => $opportunity->lead->full_name,
                        'company' => $opportunity->lead->company,
                    ] : null,
                ])
                ->values(),
            'data' => $quotations->map(fn (Quotation $quotation) => $this->transformQuotation($quotation))->values(),
            'placeholder' => [
                'approval' => 'Approval workflow stores status transitions and notes. Multi-step approver routing can be connected in the next sprint.',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opportunityId' => ['required', 'integer', 'exists:opportunities,id'],
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'validUntil' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:draft,submitted,approved,rejected'],
            'approvalNotes' => ['nullable', 'string'],
        ]);

        $status = $validated['status'] ?? 'draft';

        $quotation = Quotation::query()->create([
            'opportunity_id' => $validated['opportunityId'],
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'IDR',
            'valid_until' => $validated['validUntil'] ?? null,
            'status' => $status,
            'approval_notes' => $validated['approvalNotes'] ?? null,
            'submitted_at' => $status === 'submitted' ? now() : null,
            'approved_at' => $status === 'approved' ? now() : null,
            'rejected_at' => $status === 'rejected' ? now() : null,
            'metadata' => [
                'placeholder' => 'Quotation generator is ready for PDF/export integration in the next sprint.',
            ],
        ]);

        $quotation->forceFill([
            'quote_number' => sprintf('QTN-%06d', $quotation->id),
        ])->save();

        return response()->json([
            'message' => 'Quotation created successfully.',
            'data' => $this->transformQuotation($quotation->fresh(['opportunity.lead'])),
        ], 201);
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:draft,submitted,approved,rejected'],
            'title' => ['nullable', 'string', 'max:160'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'validUntil' => ['nullable', 'date'],
            'approvalNotes' => ['nullable', 'string'],
        ]);

        if (array_key_exists('status', $validated)) {
            $quotation->status = $validated['status'];
            $quotation->submitted_at = $validated['status'] === 'submitted' ? now() : null;
            $quotation->approved_at = $validated['status'] === 'approved' ? now() : null;
            $quotation->rejected_at = $validated['status'] === 'rejected' ? now() : null;
        }

        if (array_key_exists('title', $validated)) {
            $quotation->title = $validated['title'];
        }

        if (array_key_exists('amount', $validated)) {
            $quotation->amount = $validated['amount'];
        }

        if (array_key_exists('validUntil', $validated)) {
            $quotation->valid_until = $validated['validUntil'];
        }

        if (array_key_exists('approvalNotes', $validated)) {
            $quotation->approval_notes = $validated['approvalNotes'];
        }

        $quotation->save();

        return response()->json([
            'message' => 'Quotation updated successfully.',
            'data' => $this->transformQuotation($quotation->fresh(['opportunity.lead'])),
        ]);
    }

    private function transformQuotation(Quotation $quotation): array
    {
        return [
            'id' => $quotation->id,
            'quoteNumber' => $quotation->quote_number,
            'opportunityId' => $quotation->opportunity_id,
            'opportunity_id' => $quotation->opportunity_id,
            'title' => $quotation->title,
            'amount' => (float) $quotation->amount,
            'currency' => $quotation->currency,
            'validUntil' => optional($quotation->valid_until)->toDateString(),
            'status' => $quotation->status,
            'approvalNotes' => $quotation->approval_notes,
            'submittedAt' => optional($quotation->submitted_at)->toIso8601String(),
            'approvedAt' => optional($quotation->approved_at)->toIso8601String(),
            'rejectedAt' => optional($quotation->rejected_at)->toIso8601String(),
            'opportunity' => $quotation->opportunity ? [
                'id' => $quotation->opportunity->id,
                'code' => $quotation->opportunity->code,
                'name' => $quotation->opportunity->name,
                'stage' => $quotation->opportunity->stage,
                'lead' => $quotation->opportunity->lead ? [
                    'id' => $quotation->opportunity->lead->id,
                    'fullName' => $quotation->opportunity->lead->full_name,
                    'company' => $quotation->opportunity->lead->company,
                ] : null,
            ] : null,
            'metadata' => $quotation->metadata,
        ];
    }
}