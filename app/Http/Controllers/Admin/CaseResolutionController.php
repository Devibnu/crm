<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseResolution;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaseResolutionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $resolutionType = trim((string) $request->query('resolution_type', ''));
        $customerNotified = trim((string) $request->query('customer_notified', ''));

        $resolutions = CaseResolution::query()
            ->with('ticket:id,ticket_number,subject')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filterResolutionType($resolutionType, $this->resolutionTypeOptions())
            ->filterCustomerNotified($customerNotified)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => CaseResolution::query()->count(),
            'fixed' => CaseResolution::query()->where('resolution_type', 'fixed')->count(),
            'escalated' => CaseResolution::query()->where('resolution_type', 'escalated')->count(),
            'customer_notified' => CaseResolution::query()->where('customer_notified', true)->count(),
        ];

        return view('admin.service.case-resolutions.index', [
            'resolutions' => $resolutions,
            'search' => $search,
            'selectedResolutionType' => $resolutionType,
            'selectedCustomerNotified' => $customerNotified,
            'resolutionTypeOptions' => $this->resolutionTypeOptions(),
            'customerNotifiedOptions' => $this->customerNotifiedOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.case-resolutions.create', [
            'resolution' => null,
            'tickets' => Ticket::query()->latest()->get(['id', 'ticket_number', 'subject']),
            'resolutionTypeOptions' => $this->resolutionTypeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resolution = CaseResolution::create($this->validatedData($request));

        return redirect()
            ->route('admin.service.case-resolutions.show', $resolution)
            ->with('success', 'Case resolution berhasil ditambahkan.');
    }

    public function show(CaseResolution $caseResolution): View
    {
        return view('admin.service.case-resolutions.show', [
            'resolution' => $caseResolution->load('ticket:id,ticket_number,subject'),
        ]);
    }

    public function edit(CaseResolution $caseResolution): View
    {
        return view('admin.service.case-resolutions.edit', [
            'resolution' => $caseResolution,
            'tickets' => Ticket::query()->latest()->get(['id', 'ticket_number', 'subject']),
            'resolutionTypeOptions' => $this->resolutionTypeOptions(),
        ]);
    }

    public function update(Request $request, CaseResolution $caseResolution): RedirectResponse
    {
        $caseResolution->update($this->validatedData($request));

        return redirect()
            ->route('admin.service.case-resolutions.show', $caseResolution)
            ->with('success', 'Case resolution berhasil diperbarui.');
    }

    public function destroy(CaseResolution $caseResolution): RedirectResponse
    {
        $caseResolution->delete();

        return redirect()
            ->route('admin.service.case-resolutions.index')
            ->with('success', 'Case resolution berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'ticket_id' => ['required', 'exists:tickets,id'],
            'resolution_summary' => ['required', 'string', 'max:255'],
            'resolution_notes' => ['nullable', 'string'],
            'root_cause' => ['nullable', 'string'],
            'resolution_type' => ['required', Rule::in($this->resolutionTypeOptions())],
            'resolved_by' => ['nullable', 'string', 'max:255'],
            'resolved_at' => ['nullable', 'date'],
            'customer_notified' => ['required', 'boolean'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function resolutionTypeOptions(): array
    {
        return ['workaround', 'fixed', 'duplicate', 'invalid', 'escalated'];
    }

    /**
     * @return array<string, string>
     */
    protected function customerNotifiedOptions(): array
    {
        return [
            'yes' => 'Notified',
            'no' => 'Not notified',
        ];
    }
}
