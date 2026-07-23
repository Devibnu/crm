<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateSlaPolicyRequest;
use App\Http\Requests\Admin\UpdateSlaPolicyRequest;
use App\Models\SlaPolicy;
use App\Services\Sla\SlaPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaPolicyController extends Controller
{
    public function __construct(
        protected SlaPolicyService $slaPolicyService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $priority = trim((string) $request->query('priority', ''));
        $active = trim((string) $request->query('is_active', ''));

        $policies = SlaPolicy::query()
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filterPriority($priority, SlaPolicy::priorityOptions())
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
            'priorityOptions' => SlaPolicy::priorityOptions(),
            'activeOptions' => SlaPolicy::activeOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.sla.create', [
            'policy' => null,
            'priorityOptions' => SlaPolicy::priorityOptions(),
        ]);
    }

    public function store(CreateSlaPolicyRequest $request): RedirectResponse
    {
        $policy = $this->slaPolicyService->create($request->policyData());

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
            'priorityOptions' => SlaPolicy::priorityOptions(),
        ]);
    }

    public function update(UpdateSlaPolicyRequest $request, SlaPolicy $sla): RedirectResponse
    {
        $this->slaPolicyService->update($sla, $request->policyData());

        return redirect()
            ->route('admin.service.sla.show', $sla)
            ->with('success', 'SLA policy berhasil diperbarui.');
    }

    public function destroy(SlaPolicy $sla): RedirectResponse
    {
        $this->slaPolicyService->delete($sla);

        return redirect()
            ->route('admin.service.sla.index')
            ->with('success', 'SLA policy berhasil dihapus.');
    }

}
