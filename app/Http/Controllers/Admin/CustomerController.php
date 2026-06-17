<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\SalesActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function profile(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $customerId = $request->integer('customer_id');

        if (! $customerId) {
            $customers = Customer::query()
                ->withCount(['interactions', 'transactions'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($innerQuery) use ($search) {
                        $innerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('whatsapp', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(10)
                ->withQueryString();

            return view('admin.customers.profile', [
                'customer' => null,
                'customers' => $customers,
                'search' => $search,
            ]);
        }

        $customer = Customer::query()
            ->withCount(['interactions', 'transactions', 'preferences', 'behaviors'])
            ->findOrFail($customerId);

        $recentInteractions = $customer->interactions()
            ->orderByRaw('interaction_at IS NULL')
            ->orderByDesc('interaction_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $recentTransactions = $customer->transactions()
            ->orderByRaw('closing_date IS NULL')
            ->orderByDesc('closing_date')
            ->latest('id')
            ->limit(8)
            ->get();

        $recentPreferences = $customer->preferences()
            ->latest()
            ->limit(8)
            ->get();

        $recentBehaviors = $customer->behaviors()
            ->orderByDesc('last_activity_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $recentOpportunities = Opportunity::query()
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(8)
            ->get();

        $recentQuotations = Quotation::query()
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.customers.profile', [
            'customer' => $customer,
            'customers' => null,
            'search' => $search,
            'summary' => [
                'interactions' => $customer->interactions_count,
                'transactions' => $customer->transactions_count,
                'preferences' => $customer->preferences_count,
                'behaviors' => $customer->behaviors_count,
                'opportunities' => Opportunity::query()->where('customer_id', $customer->id)->count(),
                'quotations' => Quotation::query()->where('customer_id', $customer->id)->count(),
            ],
            'latestInteraction' => $recentInteractions->first(),
            'latestTransaction' => $recentTransactions->first(),
            'latestPreference' => $recentPreferences->first(),
            'latestBehavior' => $recentBehaviors->first(),
            'recentInteractions' => $recentInteractions,
            'recentTransactions' => $recentTransactions,
            'recentPreferences' => $recentPreferences,
            'recentBehaviors' => $recentBehaviors,
            'recentOpportunities' => $recentOpportunities,
            'recentQuotations' => $recentQuotations,
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.create', [
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        Customer::create($validated);

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Customer berhasil ditambahkan.');
    }

    public function show(Customer $customer): View
    {
        $recentSalesActivities = SalesActivity::query()
            ->where('related_type', 'customer')
            ->where('related_id', $customer->id)
            ->orderByRaw('activity_at IS NULL')
            ->orderByDesc('activity_at')
            ->latest('id')
            ->limit(5)
            ->get();

        $recentQuotations = Quotation::query()
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.customers.show', [
            'customer' => $customer,
            'recentSalesActivities' => $recentSalesActivities,
            'recentQuotations' => $recentQuotations,
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', [
            'customer' => $customer,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $customer->update($validated);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Customer berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['new', 'active', 'inactive', 'blacklist'];
    }
}
