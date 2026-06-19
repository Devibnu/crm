<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Services\QuotationOutcomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('admin.sales.deals.create', [
            'opportunities' => Opportunity::query()->orderBy('title')->get(['id', 'title']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'prefillOpportunityId' => $request->query('opportunity_id'),
            'prefillCustomerId' => $request->query('customer_id'),
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
            'quotation' => $quotation->load(['customer:id,name', 'opportunity:id,title']),
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

        $validated['opportunity_id'] = $validated['opportunity_id'] ?? null;
        $validated['customer_id'] = $validated['customer_id'] ?? null;

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['draft', 'sent', 'accepted', 'rejected', 'expired'];
    }
}
