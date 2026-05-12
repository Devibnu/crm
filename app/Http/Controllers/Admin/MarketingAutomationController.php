<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudienceSegment;
use App\Models\MarketingAutomation;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingAutomationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $trigger = trim((string) $request->query('trigger_type', ''));
        $action = trim((string) $request->query('action_type', ''));
        $status = trim((string) $request->query('status', ''));

        $automations = MarketingAutomation::query()
            ->with(['marketingCampaign:id,name', 'audienceSegment:id,name'])
            ->search($search)
            ->filterTrigger($trigger, $this->triggerOptions())
            ->filterAction($action, $this->actionOptions())
            ->filterStatus($status, $this->statusOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => MarketingAutomation::query()->count(),
            'active' => MarketingAutomation::query()->where('status', 'active')->count(),
            'paused' => MarketingAutomation::query()->where('status', 'paused')->count(),
            'executed' => MarketingAutomation::query()->sum('executed_count'),
        ];

        return view('admin.marketing.automations.index', [
            'automations' => $automations,
            'search' => $search,
            'selectedTrigger' => $trigger,
            'selectedAction' => $action,
            'selectedStatus' => $status,
            'triggerOptions' => $this->triggerOptions(),
            'actionOptions' => $this->actionOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.marketing.automations.create', [
            'automation' => null,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'segments' => AudienceSegment::query()->orderBy('name')->get(['id', 'name']),
            'triggerOptions' => $this->triggerOptions(),
            'actionOptions' => $this->actionOptions(),
            'statusOptions' => $this->statusOptions(),
            'conditionsJson' => '',
            'actionPayloadJson' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $automation = MarketingAutomation::create($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.automations.show', $automation)
            ->with('success', 'Marketing automation berhasil ditambahkan.');
    }

    public function show(MarketingAutomation $automation): View
    {
        return view('admin.marketing.automations.show', [
            'automation' => $automation->load(['marketingCampaign:id,name', 'audienceSegment:id,name']),
            'conditionsJson' => $this->prettyJson($automation->conditions),
            'actionPayloadJson' => $this->prettyJson($automation->action_payload),
        ]);
    }

    public function edit(MarketingAutomation $automation): View
    {
        return view('admin.marketing.automations.edit', [
            'automation' => $automation,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'segments' => AudienceSegment::query()->orderBy('name')->get(['id', 'name']),
            'triggerOptions' => $this->triggerOptions(),
            'actionOptions' => $this->actionOptions(),
            'statusOptions' => $this->statusOptions(),
            'conditionsJson' => $this->prettyJson($automation->conditions),
            'actionPayloadJson' => $this->prettyJson($automation->action_payload),
        ]);
    }

    public function update(Request $request, MarketingAutomation $automation): RedirectResponse
    {
        $automation->update($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.automations.show', $automation)
            ->with('success', 'Marketing automation berhasil diperbarui.');
    }

    public function destroy(MarketingAutomation $automation): RedirectResponse
    {
        $automation->delete();

        return redirect()
            ->route('admin.marketing.automations.index')
            ->with('success', 'Marketing automation berhasil dihapus.');
    }

    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'audience_segment_id' => ['nullable', 'exists:audience_segments,id'],
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', Rule::in($this->triggerOptions())],
            'action_type' => ['required', Rule::in($this->actionOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'delay_minutes' => ['nullable', 'integer', 'min:0'],
            'conditions' => ['nullable', 'json'],
            'action_payload' => ['nullable', 'json'],
            'executed_count' => ['nullable', 'integer', 'min:0'],
            'last_executed_at' => ['nullable', 'date'],
            'created_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        foreach (['conditions', 'action_payload'] as $jsonField) {
            $jsonValue = trim((string) ($validated[$jsonField] ?? ''));
            $validated[$jsonField] = $jsonValue === '' ? null : json_decode($jsonValue, true);
        }

        $validated['marketing_campaign_id'] = $validated['marketing_campaign_id'] ?? null;
        $validated['audience_segment_id'] = $validated['audience_segment_id'] ?? null;
        $validated['delay_minutes'] = $validated['delay_minutes'] ?? 0;
        $validated['executed_count'] = $validated['executed_count'] ?? 0;

        return $validated;
    }

    protected function triggerOptions(): array
    {
        return ['form_submit', 'lead_created', 'campaign_opened', 'link_clicked', 'manual'];
    }

    protected function actionOptions(): array
    {
        return ['send_email', 'send_whatsapp', 'assign_sales', 'add_to_segment', 'create_task'];
    }

    protected function statusOptions(): array
    {
        return ['draft', 'active', 'paused', 'completed'];
    }

    protected function prettyJson(?array $value): string
    {
        if ($value === null || $value === []) {
            return '';
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
