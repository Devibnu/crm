<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $transactions = CustomerTransaction::query()
            ->with('customer:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($status, $this->statusOptions(), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByRaw('closing_date IS NULL')
            ->orderByDesc('closing_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.transactions_crud.index', [
            'transactions' => $transactions,
            'search' => $search,
            'selectedStatus' => $status,
            'statusOptions' => $this->statusOptions(),
            'firstCustomerId' => Customer::query()->orderBy('id')->value('id'),
        ]);
    }

    public function create(Customer $customer): View
    {
        return view('admin.customers.transactions_crud.create', [
            'selectedCustomer' => $customer,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        unset($customer);

        $validated = $request->validate($this->rules());

        CustomerTransaction::create($validated);

        return redirect()
            ->route('admin.customers.transactions')
            ->with('success', 'Transaction berhasil ditambahkan.');
    }

    public function edit(CustomerTransaction $transaction): View
    {
        return view('admin.customers.transactions_crud.edit', [
            'transaction' => $transaction,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, CustomerTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $transaction->update($validated);

        return redirect()
            ->route('admin.customers.transactions')
            ->with('success', 'Transaction berhasil diperbarui.');
    }

    public function destroy(CustomerTransaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return redirect()
            ->route('admin.customers.transactions')
            ->with('success', 'Transaction berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'closing_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['pending', 'won', 'lost', 'cancelled'];
    }
}
