<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadScoringRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadScoringRuleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $trigger = trim((string) $request->query('trigger_source', ''));
        $priority = trim((string) $request->query('priority', ''));
        $status = trim((string) $request->query('status', ''));

        $rules = LeadScoringRule::query()
            ->search($search)
            ->filterTrigger($trigger, $this->triggerOptions())
            ->filterPriority($priority, $this->priorityOptions())
            ->filterStatus($status, $this->statusOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => LeadScoringRule::query()->count(),
            'active' => LeadScoringRule::query()->where('status', 'active')->count(),
            'auto_assign' => LeadScoringRule::query()->where('auto_assign', true)->count(),
            'executions' => LeadScoringRule::query()->sum('execution_count'),
        ];

        return view('admin.marketing.lead-scoring.index', [
            'rules' => $rules,
            'search' => $search,
            'selectedTrigger' => $trigger,
            'selectedPriority' => $priority,
            'selectedStatus' => $status,
            'triggerOptions' => $this->triggerOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.marketing.lead-scoring.create', [
            'rule' => null,
            'triggerOptions' => $this->triggerOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'statusOptions' => $this->statusOptions(),
            'conditionsJson' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rule = LeadScoringRule::create($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.lead-scoring.show', $rule)
            ->with('success', 'Lead scoring rule berhasil ditambahkan.');
    }

    public function show(LeadScoringRule $leadScoring): View
    {
        return view('admin.marketing.lead-scoring.show', [
            'rule' => $leadScoring,
            'conditionsJson' => $this->prettyJson($leadScoring->conditions),
        ]);
    }

    public function edit(LeadScoringRule $leadScoring): View
    {
        return view('admin.marketing.lead-scoring.edit', [
            'rule' => $leadScoring,
            'triggerOptions' => $this->triggerOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'statusOptions' => $this->statusOptions(),
            'conditionsJson' => $this->prettyJson($leadScoring->conditions),
        ]);
    }

    public function update(Request $request, LeadScoringRule $leadScoring): RedirectResponse
    {
        $leadScoring->update($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.lead-scoring.show', $leadScoring)
            ->with('success', 'Lead scoring rule berhasil diperbarui.');
    }

    public function destroy(LeadScoringRule $leadScoring): RedirectResponse
    {
        $leadScoring->delete();

        return redirect()
            ->route('admin.marketing.lead-scoring.index')
            ->with('success', 'Lead scoring rule berhasil dihapus.');
    }

    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_source' => ['required', Rule::in($this->triggerOptions())],
            'score_value' => ['nullable', 'integer', 'min:0', 'max:100'],
            'routing_team' => ['nullable', 'string', 'max:255'],
            'routing_user' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'json'],
            'priority' => ['required', Rule::in($this->priorityOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'auto_assign' => ['nullable', 'boolean'],
            'execution_count' => ['nullable', 'integer', 'min:0'],
            'last_executed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        $conditions = trim((string) ($validated['conditions'] ?? ''));
        $validated['conditions'] = $conditions === '' ? null : json_decode($conditions, true);
        $validated['score_value'] = $validated['score_value'] ?? 0;
        $validated['auto_assign'] = $request->boolean('auto_assign');
        $validated['execution_count'] = $validated['execution_count'] ?? 0;

        return $validated;
    }

    protected function triggerOptions(): array
    {
        return ['form_submit', 'campaign_engagement', 'social_engagement', 'manual', 'crm_activity'];
    }

    protected function priorityOptions(): array
    {
        return ['low', 'medium', 'high'];
    }

    protected function statusOptions(): array
    {
        return ['active', 'inactive'];
    }

    protected function prettyJson(?array $value): string
    {
        if ($value === null || $value === []) {
            return '';
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
