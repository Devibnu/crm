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

class SalesActivityController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', $request->query('search', '')));
        $type = trim((string) $request->query('type', ''));
        $relatedType = trim((string) $request->query('related_type', ''));

        $activities = SalesActivity::query()
            ->with([
                'relatedLead:id,name',
                'relatedOpportunity:id,title',
                'relatedCustomer:id,name',
            ])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when(in_array($type, $this->typeOptions(), true), fn ($query) => $query->where('type', $type))
            ->when(in_array($relatedType, $this->relatedTypeOptions(), true), fn ($query) => $query->where('related_type', $relatedType))
            ->orderByRaw('activity_at IS NULL')
            ->orderByDesc('activity_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => SalesActivity::query()->count(),
            'calls' => SalesActivity::query()->where('type', 'call')->count(),
            'meetings' => SalesActivity::query()->where('type', 'meeting')->count(),
            'followUps' => SalesActivity::query()->where('type', 'follow_up')->count(),
        ];

        return view('admin.sales.activities.index', [
            'activities' => $activities,
            'search' => $search,
            'selectedType' => $type,
            'selectedRelatedType' => $relatedType,
            'typeOptions' => $this->typeOptions(),
            'relatedTypeOptions' => $this->relatedTypeOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.sales.activities.create', [
            'activity' => null,
            'typeOptions' => $this->typeOptions(),
            'relatedTypeOptions' => $this->relatedTypeOptions(),
            'relatedOptions' => $this->relatedOptions(),
            'prefillRelatedType' => $request->query('related_type'),
            'prefillRelatedId' => $request->query('related_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $activity = SalesActivity::create($this->validatedData($request));

        return redirect()
            ->route('admin.sales.activities.show', $activity)
            ->with('success', 'Sales activity berhasil ditambahkan.');
    }

    public function show(SalesActivity $activity): View
    {
        return view('admin.sales.activities.show', [
            'activity' => $activity->load([
                'relatedLead:id,name',
                'relatedOpportunity:id,title',
                'relatedCustomer:id,name',
            ]),
        ]);
    }

    public function edit(SalesActivity $activity): View
    {
        return view('admin.sales.activities.edit', [
            'activity' => $activity,
            'typeOptions' => $this->typeOptions(),
            'relatedTypeOptions' => $this->relatedTypeOptions(),
            'relatedOptions' => $this->relatedOptions(),
            'prefillRelatedType' => null,
            'prefillRelatedId' => null,
        ]);
    }

    public function update(Request $request, SalesActivity $activity): RedirectResponse
    {
        $activity->update($this->validatedData($request));

        return redirect()
            ->route('admin.sales.activities.show', $activity)
            ->with('success', 'Sales activity berhasil diperbarui.');
    }

    public function destroy(SalesActivity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect()
            ->route('admin.sales.activities.index')
            ->with('success', 'Sales activity berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'related_type' => ['required', Rule::in($this->relatedTypeOptions())],
            'related_id' => ['required', 'integer'],
            'type' => ['required', Rule::in($this->typeOptions())],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'outcome' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function relatedTypeOptions(): array
    {
        return ['lead', 'opportunity', 'customer'];
    }

    /**
     * @return array<int, string>
     */
    protected function typeOptions(): array
    {
        return ['call', 'whatsapp', 'email', 'meeting', 'note', 'follow_up'];
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, object>>
     */
    protected function relatedOptions(): array
    {
        return [
            'lead' => Lead::query()->orderBy('name')->get(['id', 'name']),
            'opportunity' => Opportunity::query()->orderBy('title')->get(['id', 'title']),
            'customer' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
