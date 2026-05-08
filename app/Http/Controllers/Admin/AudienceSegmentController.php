<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudienceSegment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AudienceSegmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));

        $segments = AudienceSegment::query()
            ->search($search)
            ->filterType($type, $this->typeOptions())
            ->filterStatus($status, $this->statusOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => AudienceSegment::query()->count(),
            'active' => AudienceSegment::query()->where('status', 'active')->count(),
            'inactive' => AudienceSegment::query()->where('status', 'inactive')->count(),
            'estimated_audience' => AudienceSegment::query()->sum('estimated_audience'),
        ];

        return view('admin.marketing.audiences.index', [
            'segments' => $segments,
            'search' => $search,
            'selectedType' => $type,
            'selectedStatus' => $status,
            'typeOptions' => $this->typeOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.marketing.audiences.create', [
            'segment' => null,
            'typeOptions' => $this->typeOptions(),
            'statusOptions' => $this->statusOptions(),
            'criteriaJson' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $segment = AudienceSegment::create($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.audiences.show', $segment)
            ->with('success', 'Audience segment berhasil ditambahkan.');
    }

    public function show(AudienceSegment $audience): View
    {
        return view('admin.marketing.audiences.show', [
            'segment' => $audience,
            'criteriaJson' => $this->prettyCriteria($audience),
        ]);
    }

    public function edit(AudienceSegment $audience): View
    {
        return view('admin.marketing.audiences.edit', [
            'segment' => $audience,
            'typeOptions' => $this->typeOptions(),
            'statusOptions' => $this->statusOptions(),
            'criteriaJson' => $this->prettyCriteria($audience),
        ]);
    }

    public function update(Request $request, AudienceSegment $audience): RedirectResponse
    {
        $audience->update($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.audiences.show', $audience)
            ->with('success', 'Audience segment berhasil diperbarui.');
    }

    public function destroy(AudienceSegment $audience): RedirectResponse
    {
        $audience->delete();

        return redirect()
            ->route('admin.marketing.audiences.index')
            ->with('success', 'Audience segment berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($this->typeOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'description' => ['nullable', 'string'],
            'criteria' => ['nullable', 'json'],
            'estimated_audience' => ['nullable', 'integer', 'min:0'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        $criteria = trim((string) ($validated['criteria'] ?? ''));
        $validated['criteria'] = $criteria === '' ? null : json_decode($criteria, true);
        $validated['estimated_audience'] = $validated['estimated_audience'] ?? 0;

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function typeOptions(): array
    {
        return ['demographic', 'behavioral', 'transactional', 'engagement'];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['active', 'inactive'];
    }

    protected function prettyCriteria(AudienceSegment $segment): string
    {
        if ($segment->criteria === null || $segment->criteria === []) {
            return '';
        }

        return json_encode($segment->criteria, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
