<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\SalesActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));

        $leads = Lead::query()
            ->with('customer:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('assigned_to', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $this->statusOptions(), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(in_array($priority, $this->priorityOptions(), true), function ($query) use ($priority) {
                $query->where('priority', $priority);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.sales.leads.index', [
            'leads' => $leads,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPriority' => $priority,
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.sales.leads.create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        Lead::create($validated);

        return redirect()
            ->route('admin.sales.leads')
            ->with('success', 'Lead berhasil ditambahkan.');
    }

    public function show(Lead $lead): View
    {
        $recentActivities = SalesActivity::where('related_type', 'lead')
            ->where('related_id', $lead->id)
            ->orderByRaw('activity_at IS NULL')
            ->orderByDesc('activity_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('admin.sales.leads.show', [
            'lead' => $lead,
            'recentActivities' => $recentActivities,
        ]);
    }

    public function edit(Lead $lead): View
    {
        return view('admin.sales.leads.edit', [
            'lead' => $lead,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $lead->update($validated);

        return redirect()
            ->route('admin.sales.leads.show', $lead)
            ->with('success', 'Lead berhasil diperbarui.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()
            ->route('admin.sales.leads')
            ->with('success', 'Lead berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'priority' => ['required', Rule::in($this->priorityOptions())],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['customer_id'] = $validated['customer_id'] ?? null;

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['new', 'contacted', 'qualified', 'unqualified', 'converted'];
    }

    /**
     * @return array<int, string>
     */
    protected function priorityOptions(): array
    {
        return ['low', 'medium', 'high'];
    }
}
