<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerBehavior;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerBehaviorController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $lifecycleStage = trim((string) $request->query('lifecycle_stage', ''));

        $behaviors = CustomerBehavior::query()
            ->with('customer:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('product_interest', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($lifecycleStage, $this->lifecycleStageOptions(), true), function ($query) use ($lifecycleStage) {
                $query->where('lifecycle_stage', $lifecycleStage);
            })
            ->orderByDesc('engagement_score')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.behavior_crud.index', [
            'behaviors' => $behaviors,
            'search' => $search,
            'selectedLifecycleStage' => $lifecycleStage,
            'lifecycleStageOptions' => $this->lifecycleStageOptions(),
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(['id', 'name', 'company_name', 'email', 'phone']),
        ]);
    }

    public function create(Customer $customer): View
    {
        return view('admin.customers.behavior_crud.create', [
            'selectedCustomer' => $customer,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'lifecycleStageOptions' => $this->lifecycleStageOptions(),
        ]);
    }

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $request->merge(['customer_id' => $customer->id]);

        $validated = $request->validate($this->rules());
        $validated['customer_id'] = $customer->id;

        CustomerBehavior::create($validated);

        return redirect()
            ->route('admin.customers.behavior')
            ->with('success', 'Behavior berhasil ditambahkan.');
    }

    public function edit(CustomerBehavior $behavior): View
    {
        return view('admin.customers.behavior_crud.edit', [
            'behavior' => $behavior,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'lifecycleStageOptions' => $this->lifecycleStageOptions(),
        ]);
    }

    public function update(Request $request, CustomerBehavior $behavior): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $behavior->update($validated);

        return redirect()
            ->route('admin.customers.behavior')
            ->with('success', 'Behavior berhasil diperbarui.');
    }

    public function destroy(CustomerBehavior $behavior): RedirectResponse
    {
        $behavior->delete();

        return redirect()
            ->route('admin.customers.behavior')
            ->with('success', 'Behavior berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'lifecycle_stage' => ['required', Rule::in($this->lifecycleStageOptions())],
            'engagement_score' => ['required', 'integer', 'min:0', 'max:100'],
            'last_activity_at' => ['nullable', 'date'],
            'product_interest' => ['nullable', 'string', 'max:255'],
            'behavior_notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function lifecycleStageOptions(): array
    {
        return ['lead', 'prospect', 'active', 'loyal', 'churned'];
    }
}
