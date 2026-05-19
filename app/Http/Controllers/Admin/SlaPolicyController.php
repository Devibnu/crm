<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlaPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SlaPolicyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $priority = trim((string) $request->query('priority', ''));
        $active = trim((string) $request->query('is_active', ''));

        $policies = SlaPolicy::query()
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filterPriority($priority, $this->priorityOptions())
            ->filterActive($active)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $averageResolution = (float) SlaPolicy::query()->avg('resolution_time_minutes');

        $summary = [
            'total' => SlaPolicy::query()->count(),
            'active' => SlaPolicy::query()->where('is_active', true)->count(),
            'high_urgent' => SlaPolicy::query()->whereIn('priority', ['high', 'urgent'])->count(),
            'average_resolution' => $averageResolution,
        ];

        return view('admin.service.sla.index', [
            'policies' => $policies,
            'search' => $search,
            'selectedPriority' => $priority,
            'selectedActive' => $active,
            'priorityOptions' => $this->priorityOptions(),
            'activeOptions' => $this->activeOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.sla.create', [
            'policy' => null,
            'priorityOptions' => $this->priorityOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $policy = SlaPolicy::create($this->validatedData($request));

        return redirect()
            ->route('admin.service.sla.show', $policy)
            ->with('success', 'SLA policy berhasil ditambahkan.');
    }

    public function show(SlaPolicy $sla): View
    {
        return view('admin.service.sla.show', [
            'policy' => $sla,
        ]);
    }

    public function edit(SlaPolicy $sla): View
    {
        return view('admin.service.sla.edit', [
            'policy' => $sla,
            'priorityOptions' => $this->priorityOptions(),
        ]);
    }

    public function update(Request $request, SlaPolicy $sla): RedirectResponse
    {
        $sla->update($this->validatedData($request));

        return redirect()
            ->route('admin.service.sla.show', $sla)
            ->with('success', 'SLA policy berhasil diperbarui.');
    }

    public function destroy(SlaPolicy $sla): RedirectResponse
    {
        $sla->delete();

        return redirect()
            ->route('admin.service.sla.index')
            ->with('success', 'SLA policy berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in($this->priorityOptions())],
            'response_time_minutes' => ['required', 'integer', 'min:1'],
            'resolution_time_minutes' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function priorityOptions(): array
    {
        return ['low', 'medium', 'high', 'urgent'];
    }

    /**
     * @return array<string, string>
     */
    protected function activeOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }
}
