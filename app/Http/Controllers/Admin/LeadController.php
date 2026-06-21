<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
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
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $leads = Lead::query()
            ->with('customer:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('lead_source', 'like', "%{$search}%")
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
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.sales.leads.index', [
            'leads' => $leads,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPriority' => $priority,
            'selectedPerPage' => $perPage,
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'summary' => [
                'total' => Lead::query()->count(),
                'new' => Lead::query()->where('status', 'new')->count(),
                'qualified' => Lead::query()->where('status', 'qualified')->count(),
                'converted' => Lead::query()->where('status', 'converted')->count(),
            ],
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
        $lead->loadMissing('sourceWhatsappConversation:id,contact_name,phone_number');
        $activeOpportunity = Opportunity::query()
            ->where('lead_id', $lead->id)
            ->where('status', '!=', 'lost')
            ->latest()
            ->first();

        $recentActivities = SalesActivity::where('related_type', 'lead')
            ->where('related_id', $lead->id)
            ->orderByRaw('activity_at IS NULL')
            ->orderByDesc('activity_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('admin.sales.leads.show', [
            'lead' => $lead,
            'activeOpportunity' => $activeOpportunity,
            'recentActivities' => $recentActivities,
        ]);
    }

    public function convertToOpportunity(Lead $lead): RedirectResponse
    {
        $existingOpportunity = Opportunity::query()
            ->where('lead_id', $lead->id)
            ->where('status', '!=', 'lost')
            ->latest()
            ->first();

        if ($existingOpportunity) {
            return redirect()
                ->route('admin.sales.opportunities.show', $existingOpportunity)
                ->with('success', 'Opportunity untuk lead ini sudah ada.');
        }

        $opportunity = Opportunity::create([
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'title' => 'Opportunity - '.$lead->name,
            'company_name' => $lead->company_name,
            'contact_name' => $lead->name,
            'probability' => $this->probabilityForTemperature($lead->lead_temperature),
            'status' => 'open',
            'assigned_to' => $lead->assigned_to,
            'notes' => implode("\n", [
                'Source: WhatsApp',
                'Campaign: '.($lead->source_campaign ?: '-'),
                'Lead Score: '.((int) $lead->lead_score),
                'Temperature: '.ucfirst($lead->lead_temperature ?: 'cold'),
                'WhatsApp Conversation: '.($lead->source_whatsapp_conversation_id ?: '-'),
            ]),
        ]);

        return redirect()
            ->route('admin.sales.opportunities.show', $opportunity)
            ->with('success', 'Lead berhasil dikonversi menjadi opportunity.');
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
            'whatsapp' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:255'],
            'lead_source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'priority' => ['required', Rule::in($this->priorityOptions())],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'last_whatsapp_message' => ['nullable', 'string'],
            'last_whatsapp_at' => ['nullable', 'date'],
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

    protected function probabilityForTemperature(?string $temperature): int
    {
        return match (strtolower((string) $temperature)) {
            'hot' => 75,
            'warm' => 50,
            default => 25,
        };
    }
}
