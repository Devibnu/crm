<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInteraction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerInteractionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));

        $interactions = CustomerInteraction::query()
            ->with('customer:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('subject', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('handled_by', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($type, $this->typeOptions(), true), function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->orderByRaw('interaction_at IS NULL')
            ->orderByDesc('interaction_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.interactions.index', [
            'interactions' => $interactions,
            'search' => $search,
            'selectedType' => $type,
            'typeOptions' => $this->typeOptions(),
            'firstCustomerId' => Customer::query()->orderBy('id')->value('id'),
        ]);
    }

    public function create(Customer $customer): View
    {
        return view('admin.customers.interactions.create', [
            'selectedCustomer' => $customer,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate($this->rules($customer));

        CustomerInteraction::create($validated);

        return redirect()
            ->route('admin.customers.interactions')
            ->with('success', 'Interaction berhasil ditambahkan.');
    }

    public function edit(CustomerInteraction $interaction): View
    {
        return view('admin.customers.interactions.edit', [
            'interaction' => $interaction,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function update(Request $request, CustomerInteraction $interaction): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $interaction->update($validated);

        return redirect()
            ->route('admin.customers.interactions')
            ->with('success', 'Interaction berhasil diperbarui.');
    }

    public function destroy(CustomerInteraction $interaction): RedirectResponse
    {
        $interaction->delete();

        return redirect()
            ->route('admin.customers.interactions')
            ->with('success', 'Interaction berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(?Customer $customer = null): array
    {
        return [
            'customer_id' => array_values(array_filter([
                'required',
                'exists:customers,id',
                $customer ? Rule::in([$customer->id]) : null,
            ])),
            'type' => ['required', Rule::in($this->typeOptions())],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'interaction_at' => ['nullable', 'date'],
            'handled_by' => ['nullable', 'string', 'max:255'],
            'outcome' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function typeOptions(): array
    {
        return ['call', 'whatsapp', 'email', 'meeting', 'note', 'follow_up'];
    }
}
