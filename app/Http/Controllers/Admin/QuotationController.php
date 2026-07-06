<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Services\QuotationOutcomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationOutcomeService $quotationOutcomeService
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $quotations = Quotation::query()
            ->with(['customer:id,name', 'opportunity:id,title'])
            ->when($search !== '', function ($query) use ($search) {
                $searchTerm = '%' . mb_strtolower($search) . '%';

                $query->where(function ($innerQuery) use ($searchTerm) {
                    $innerQuery
                        ->whereRaw('LOWER(quote_number) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(title) LIKE ?', [$searchTerm])
                        ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                            $customerQuery->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);
                        })
                        ->orWhereHas('opportunity', function ($opportunityQuery) use ($searchTerm) {
                            $opportunityQuery->whereRaw('LOWER(title) LIKE ?', [$searchTerm]);
                        });
                });
            })
            ->when(in_array($status, $this->statusOptions(), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => Quotation::query()->count(),
            'draft' => Quotation::query()->where('status', 'draft')->count(),
            'sent' => Quotation::query()->where('status', 'sent')->count(),
            'accepted_value' => (float) Quotation::query()->where('status', 'accepted')->sum('amount'),
        ];

        return view('admin.sales.deals.index', [
            'quotations' => $quotations,
            'search' => $search,
            'selectedStatus' => $status,
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(Request $request): View
    {
        $quotation = null;
        $sourceOpportunity = null;
        $prefillOpportunityId = $request->query('opportunity_id');
        $prefillCustomerId = $request->query('customer_id');

        if ($request->filled('opportunity_id')) {
            $sourceOpportunity = Opportunity::query()
                ->with([
                    'lead:id,name,customer_id,conversation_id,source_whatsapp_conversation_id',
                    'conversation:id,contact_name,phone_number',
                    'customer:id,name',
                ])
                ->find($request->integer('opportunity_id'));

            if ($sourceOpportunity) {
                $conversationId = $sourceOpportunity->conversation_id
                    ?: $sourceOpportunity->lead?->conversation_id
                    ?: $sourceOpportunity->lead?->source_whatsapp_conversation_id;

                $quotation = new Quotation([
                    'opportunity_id' => $sourceOpportunity->id,
                    'lead_id' => $sourceOpportunity->lead_id,
                    'customer_id' => $sourceOpportunity->customer_id ?: $sourceOpportunity->lead?->customer_id,
                    'conversation_id' => $conversationId,
                    'quote_number' => $this->generateQuoteNumber($sourceOpportunity),
                    'title' => $sourceOpportunity->title,
                    'amount' => $sourceOpportunity->estimated_value ?? 0,
                    'status' => 'draft',
                    'notes' => $this->quotationNotesFromOpportunity($sourceOpportunity),
                ]);

                $prefillOpportunityId = $sourceOpportunity->id;
                $prefillCustomerId = $sourceOpportunity->customer_id ?: $sourceOpportunity->lead?->customer_id;
            }
        }

        return view('admin.sales.deals.create', [
            'quotation' => $quotation,
            'opportunities' => Opportunity::query()->orderBy('title')->get(['id', 'title']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'prefillOpportunityId' => $prefillOpportunityId,
            'prefillCustomerId' => $prefillCustomerId,
            'sourceOpportunity' => $sourceOpportunity,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $quotation = Quotation::create($validated);

        $this->quotationOutcomeService->handle($quotation);

        return redirect()
            ->route('admin.sales.deals.index')
            ->with('success', 'Quotation berhasil ditambahkan.');
    }

    public function show(Quotation $quotation): View
    {
        return view('admin.sales.deals.show', [
            'quotation' => $quotation->load([
                'customer:id,name',
                'lead:id,name,conversation_id,source_whatsapp_conversation_id,created_at',
                'lead.conversation:id,contact_name,phone_number,created_at',
                'lead.sourceWhatsappConversation:id,contact_name,phone_number,created_at',
                'conversation:id,contact_name,phone_number,created_at',
                'opportunity:id,title,lead_id,conversation_id,status,estimated_value,won_at,lost_at,lost_reason,created_at',
                'opportunity.lead:id,name,conversation_id,source_whatsapp_conversation_id,created_at',
                'opportunity.lead.conversation:id,contact_name,phone_number,created_at',
                'opportunity.lead.sourceWhatsappConversation:id,contact_name,phone_number,created_at',
                'opportunity.conversation:id,contact_name,phone_number,created_at',
            ]),
            'lostReasonOptions' => $this->lostReasonOptions(),
        ]);
    }

    public function edit(Quotation $quotation): View
    {
        return view('admin.sales.deals.edit', [
            'quotation' => $quotation,
            'opportunities' => Opportunity::query()->orderBy('title')->get(['id', 'title']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        $validated = $this->validatedData($request, $quotation);

        $quotation->update($validated);

        $this->quotationOutcomeService->handle($quotation);

        return redirect()
            ->route('admin.sales.deals.show', $quotation)
            ->with('success', 'Quotation berhasil diperbarui.');
    }

    public function markWon(Quotation $quotation): RedirectResponse
    {
        $quotation = $this->quotationOutcomeService->markWon($quotation);

        return redirect()
            ->route('admin.sales.deals.show', $quotation)
            ->with('success', 'Quotation ditandai sebagai Won. Opportunity terkait sudah diperbarui.');
    }

    public function markLost(Request $request, Quotation $quotation): RedirectResponse
    {
        $validated = $request->validate([
            'lost_reason' => ['required', Rule::in($this->lostReasonOptions())],
        ]);

        $quotation = $this->quotationOutcomeService->markLost($quotation, $validated['lost_reason']);

        return redirect()
            ->route('admin.sales.deals.show', $quotation)
            ->with('success', 'Quotation ditandai sebagai Lost. Opportunity terkait sudah diperbarui.');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $quotation->delete();

        return redirect()
            ->route('admin.sales.deals.index')
            ->with('success', 'Quotation berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request, ?Quotation $quotation = null): array
    {
        $validated = $request->validate([
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'quote_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('quotations', 'quote_number')->ignore($quotation?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'issued_at' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string'],
        ]);

        if (Schema::hasColumn('quotations', 'conversation_id')) {
            $validated += $request->validate([
                'conversation_id' => ['nullable', 'exists:whatsapp_conversations,id'],
            ]);
            $validated['conversation_id'] = $validated['conversation_id'] ?? null;
        }

        $validated['opportunity_id'] = $validated['opportunity_id'] ?? null;
        $validated['lead_id'] = $validated['lead_id'] ?? null;
        $validated['customer_id'] = $validated['customer_id'] ?? null;

        if (filled($validated['opportunity_id'])) {
            $opportunity = Opportunity::query()
                ->with('lead:id,customer_id,conversation_id,source_whatsapp_conversation_id')
                ->find($validated['opportunity_id']);

            $validated['lead_id'] = $validated['lead_id'] ?: $opportunity?->lead_id;
            $validated['customer_id'] = $validated['customer_id'] ?: ($opportunity?->customer_id ?: $opportunity?->lead?->customer_id);

            if (Schema::hasColumn('quotations', 'conversation_id') && blank($validated['conversation_id'] ?? null)) {
                $validated['conversation_id'] = $opportunity?->conversation_id
                    ?: $opportunity?->lead?->conversation_id
                    ?: $opportunity?->lead?->source_whatsapp_conversation_id;
            }
        }

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['draft', 'sent', 'accepted', 'rejected', 'expired'];
    }

    /**
     * @return array<int, string>
     */
    protected function lostReasonOptions(): array
    {
        return QuotationOutcomeService::lostReasons();
    }

    protected function generateQuoteNumber(Opportunity $opportunity): string
    {
        $prefix = 'QTN-'.now()->format('Ymd').'-OPP'.str_pad((string) $opportunity->id, 4, '0', STR_PAD_LEFT);
        $sequence = 1;

        do {
            $quoteNumber = $prefix.'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Quotation::query()->where('quote_number', $quoteNumber)->exists());

        return $quoteNumber;
    }

    protected function quotationNotesFromOpportunity(Opportunity $opportunity): string
    {
        return collect([
            'Created from Opportunity #'.$opportunity->id.'.',
            'Opportunity: '.$opportunity->title,
            'Lead: '.($opportunity->lead?->name ?: '-'),
            'Owner: '.($opportunity->assigned_to ?: '-'),
            'Opportunity Notes: '.($opportunity->notes ?: '-'),
        ])->implode("\n");
    }
}
