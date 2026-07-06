<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\SalesActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $opportunities = Opportunity::query()
            ->with(['lead:id,name', 'customer:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('assigned_to', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $this->statusOptions(), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.sales.opportunities.index', [
            'opportunities' => $opportunities,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPerPage' => $perPage,
            'statusOptions' => $this->statusOptions(),
            'statusLabels' => $this->statusLabels(),
            'summary' => [
                'total' => Opportunity::query()->count(),
                'open' => Opportunity::query()->whereNotIn('status', ['won', 'lost'])->count(),
                'won' => Opportunity::query()->where('status', 'won')->count(),
                'lost' => Opportunity::query()->where('status', 'lost')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $prefillOpportunity = null;
        $sourceLead = null;

        if ($request->filled('lead_id')) {
            $sourceLead = Lead::query()->find($request->integer('lead_id'));

            if ($sourceLead) {
                $prefillData = [
                    'lead_id' => $sourceLead->id,
                    'customer_id' => $sourceLead->customer_id,
                    'title' => $sourceLead->name.' Opportunity',
                    'company_name' => $sourceLead->company_name,
                    'contact_name' => $sourceLead->name,
                    'estimated_value' => 0,
                    'probability' => 25,
                    'status' => 'open',
                    'assigned_to' => $sourceLead->assigned_to,
                    'notes' => $this->opportunityNotesFromLead($sourceLead),
                ];

                if (Schema::hasColumn('opportunities', 'conversation_id')) {
                    $prefillData['conversation_id'] = $sourceLead->conversation_id ?: $sourceLead->source_whatsapp_conversation_id;
                }

                $prefillOpportunity = new Opportunity($prefillData);
            }
        }

        return view('admin.sales.opportunities.create', [
            'opportunity' => $prefillOpportunity,
            'leads' => Lead::query()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'statusLabels' => $this->statusLabels(),
            'sourceLead' => $sourceLead,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        Opportunity::create($validated);

        return redirect()
            ->route('admin.sales.opportunities')
            ->with('success', 'Opportunity berhasil ditambahkan.');
    }

    public function show(Opportunity $opportunity): View
    {
        $activities = SalesActivity::where('related_type', 'opportunity')
            ->where('related_id', $opportunity->id)
            ->orderByRaw('activity_at IS NULL')
            ->orderByDesc('activity_at')
            ->latest('id')
            ->get();

        $quotations = Quotation::query()
            ->where('opportunity_id', $opportunity->id)
            ->latest()
            ->get();

        $activeQuotation = Quotation::query()
            ->where('opportunity_id', $opportunity->id)
            ->whereIn('status', $this->activeQuotationStatuses())
            ->latest()
            ->first();

        return view('admin.sales.opportunities.show', [
            'opportunity' => $opportunity->load([
                'lead:id,name,conversation_id,source_whatsapp_conversation_id',
                'lead.conversation:id,contact_name,phone_number',
                'lead.sourceWhatsappConversation:id,contact_name,phone_number',
                'conversation:id,contact_name,phone_number',
                'customer:id,name',
            ]),
            'activities' => $activities,
            'recentActivities' => $activities->take(5),
            'quotations' => $quotations,
            'recentQuotations' => $quotations->take(5),
            'activeQuotation' => $activeQuotation,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function createQuotation(Opportunity $opportunity): RedirectResponse
    {
        $activeQuotation = Quotation::query()
            ->where('opportunity_id', $opportunity->id)
            ->whereIn('status', $this->activeQuotationStatuses())
            ->latest()
            ->first();

        if ($activeQuotation) {
            return redirect()
                ->route('admin.sales.deals.show', $activeQuotation)
                ->with('success', 'Quotation aktif untuk opportunity ini sudah ada.');
        }

        $opportunity->loadMissing(['lead', 'customer']);

        $quotationData = [
            'opportunity_id' => $opportunity->id,
            'customer_id' => $opportunity->customer_id ?: $opportunity->lead?->customer_id,
            'quote_number' => $this->generateQuoteNumber($opportunity),
            'title' => $opportunity->title,
            'amount' => $opportunity->estimated_value ?? 0,
            'status' => 'draft',
            'notes' => $this->quotationNotesFromOpportunity($opportunity),
        ];

        if (Schema::hasColumn('quotations', 'lead_id')) {
            $quotationData['lead_id'] = $opportunity->lead_id;
        }

        if (Schema::hasColumn('quotations', 'conversation_id')) {
            $quotationData['conversation_id'] = $opportunity->conversation_id
                ?: $opportunity->lead?->conversation_id
                ?: $opportunity->lead?->source_whatsapp_conversation_id;
        }

        $quotation = Quotation::create($quotationData);

        return redirect()
            ->route('admin.sales.deals.show', $quotation)
            ->with('success', 'Quotation draft berhasil dibuat dari opportunity.');
    }

    public function edit(Opportunity $opportunity): View
    {
        return view('admin.sales.opportunities.edit', [
            'opportunity' => $opportunity,
            'leads' => Lead::query()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function update(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $opportunity->update($validated);

        return redirect()
            ->route('admin.sales.opportunities.show', $opportunity)
            ->with('success', 'Opportunity berhasil diperbarui.');
    }

    public function updateStage(Request $request, Opportunity $opportunity): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in($this->statusOptions())],
        ]);

        $opportunity->update(['status' => $validated['status']]);

        $stageName = $this->statusLabels()[$validated['status']];

        return response()->json([
            'message' => "Opportunity berhasil dipindahkan ke {$stageName}.",
            'status' => $validated['status'],
            'stage' => $stageName,
        ]);
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->delete();

        return redirect()
            ->route('admin.sales.opportunities')
            ->with('success', 'Opportunity berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if (Schema::hasColumn('opportunities', 'conversation_id')) {
            $validated += $request->validate([
                'conversation_id' => ['nullable', 'exists:whatsapp_conversations,id'],
            ]);
            $validated['conversation_id'] = $validated['conversation_id'] ?? null;
        }

        $validated['lead_id'] = $validated['lead_id'] ?? null;
        $validated['customer_id'] = $validated['customer_id'] ?? null;
        $validated['estimated_value'] = $validated['estimated_value'] ?? 0;
        $validated['probability'] = $validated['probability'] ?? 0;

        if (
            Schema::hasColumn('opportunities', 'conversation_id')
            && blank($validated['conversation_id'] ?? null)
            && filled($validated['lead_id'])
        ) {
            $lead = Lead::query()->find($validated['lead_id']);
            $validated['conversation_id'] = $lead?->conversation_id ?: $lead?->source_whatsapp_conversation_id;
        }

        if (filled($validated['lead_id']) && blank($validated['customer_id'])) {
            $validated['customer_id'] = Lead::query()->whereKey($validated['lead_id'])->value('customer_id');
        }

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['open', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
    }

    /**
     * @return array<int, string>
     */
    protected function activeQuotationStatuses(): array
    {
        return ['draft', 'sent', 'accepted'];
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
        $lead = $opportunity->lead;

        return collect([
            'Source: Opportunity',
            'Opportunity: '.$opportunity->title,
            'Company: '.($opportunity->company_name ?: '-'),
            'Contact: '.($opportunity->contact_name ?: '-'),
            'Lead: '.($lead?->name ?: '-'),
            'Lead Source: '.($lead?->lead_source ?: $lead?->source ?: '-'),
            'Campaign: '.($lead?->source_campaign ?: '-'),
            'WhatsApp Conversation: '.($lead?->source_whatsapp_conversation_id ?: '-'),
            'Opportunity Notes: '.($opportunity->notes ?: '-'),
        ])->implode("\n");
    }

    protected function opportunityNotesFromLead(Lead $lead): string
    {
        return 'Created from Lead #'.$lead->id.'. Source: '.($lead->source ?: '-');
    }

    /**
     * @return array<string, string>
     */
    protected function statusLabels(): array
    {
        return [
            'open' => 'Prospecting',
            'qualified' => 'Qualified',
            'proposal' => 'Proposal',
            'negotiation' => 'Negotiation',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
    }
}
