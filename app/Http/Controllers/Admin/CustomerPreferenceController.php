<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerPreferenceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $preferredChannel = trim((string) $request->query('preferred_channel', ''));
        $consent = $request->query('communication_consent', '');

        $preferences = CustomerPreference::query()
            ->with('customer:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('product_interest', 'like', "%{$search}%")
                        ->orWhere('segment', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($preferredChannel, $this->preferredChannelOptions(), true), function ($query) use ($preferredChannel) {
                $query->where('preferred_channel', $preferredChannel);
            })
            ->when($consent === '1' || $consent === '0', function ($query) use ($consent) {
                $query->where('communication_consent', $consent === '1');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.preferences_crud.index', [
            'preferences' => $preferences,
            'search' => $search,
            'selectedPreferredChannel' => $preferredChannel,
            'selectedConsent' => (string) $consent,
            'preferredChannelOptions' => $this->preferredChannelOptions(),
            'firstCustomerId' => Customer::query()->orderBy('id')->value('id'),
        ]);
    }

    public function create(Customer $customer): View
    {
        return view('admin.customers.preferences_crud.create', [
            'selectedCustomer' => $customer,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'preferredChannelOptions' => $this->preferredChannelOptions(),
        ]);
    }

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $request->merge(['customer_id' => $customer->id]);

        $validated = $request->validate($this->rules());
        $validated['customer_id'] = $customer->id;
        $validated['communication_consent'] = $request->boolean('communication_consent');

        CustomerPreference::create($validated);

        return redirect()
            ->route('admin.customers.preferences')
            ->with('success', 'Preference berhasil ditambahkan.');
    }

    public function edit(CustomerPreference $preference): View
    {
        return view('admin.customers.preferences_crud.edit', [
            'preference' => $preference,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'preferredChannelOptions' => $this->preferredChannelOptions(),
        ]);
    }

    public function update(Request $request, CustomerPreference $preference): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['communication_consent'] = $request->boolean('communication_consent');

        $preference->update($validated);

        return redirect()
            ->route('admin.customers.preferences')
            ->with('success', 'Preference berhasil diperbarui.');
    }

    public function destroy(CustomerPreference $preference): RedirectResponse
    {
        $preference->delete();

        return redirect()
            ->route('admin.customers.preferences')
            ->with('success', 'Preference berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'preferred_channel' => ['required', Rule::in($this->preferredChannelOptions())],
            'product_interest' => ['nullable', 'string', 'max:255'],
            'communication_consent' => ['nullable', 'boolean'],
            'segment' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function preferredChannelOptions(): array
    {
        return ['whatsapp', 'email', 'phone', 'meeting', 'none'];
    }
}
