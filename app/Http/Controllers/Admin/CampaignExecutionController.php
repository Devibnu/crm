<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudienceSegment;
use App\Models\CampaignExecution;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CampaignExecutionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $channel = trim((string) $request->query('channel', ''));
        $status = trim((string) $request->query('status', ''));

        $executions = CampaignExecution::query()
            ->with(['marketingCampaign:id,name', 'audienceSegment:id,name'])
            ->search($search)
            ->filterChannel($channel, $this->channelOptions())
            ->filterStatus($status, $this->statusOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => CampaignExecution::query()->count(),
            'running' => CampaignExecution::query()->where('status', 'running')->count(),
            'completed' => CampaignExecution::query()->where('status', 'completed')->count(),
            'total_sent' => CampaignExecution::query()->sum('sent_count'),
        ];

        return view('admin.marketing.executions.index', [
            'executions' => $executions,
            'search' => $search,
            'selectedChannel' => $channel,
            'selectedStatus' => $status,
            'channelOptions' => $this->channelOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.marketing.executions.create', [
            'execution' => null,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'segments' => AudienceSegment::query()->orderBy('name')->get(['id', 'name']),
            'channelOptions' => $this->channelOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $execution = CampaignExecution::create($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.executions.show', $execution)
            ->with('success', 'Campaign execution berhasil ditambahkan.');
    }

    public function show(CampaignExecution $execution): View
    {
        return view('admin.marketing.executions.show', [
            'execution' => $execution->load(['marketingCampaign:id,name', 'audienceSegment:id,name']),
            'rates' => $this->performanceRates($execution),
        ]);
    }

    public function edit(CampaignExecution $execution): View
    {
        return view('admin.marketing.executions.edit', [
            'execution' => $execution,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'segments' => AudienceSegment::query()->orderBy('name')->get(['id', 'name']),
            'channelOptions' => $this->channelOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, CampaignExecution $execution): RedirectResponse
    {
        $execution->update($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.executions.show', $execution)
            ->with('success', 'Campaign execution berhasil diperbarui.');
    }

    public function destroy(CampaignExecution $execution): RedirectResponse
    {
        $execution->delete();

        return redirect()
            ->route('admin.marketing.executions.index')
            ->with('success', 'Campaign execution berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'audience_segment_id' => ['nullable', 'exists:audience_segments,id'],
            'execution_name' => ['required', 'string', 'max:255'],
            'channel' => ['required', Rule::in($this->channelOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'scheduled_at' => ['nullable', 'date'],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'sent_count' => ['nullable', 'integer', 'min:0'],
            'delivered_count' => ['nullable', 'integer', 'min:0'],
            'opened_count' => ['nullable', 'integer', 'min:0'],
            'clicked_count' => ['nullable', 'integer', 'min:0'],
            'response_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        foreach (['sent_count', 'delivered_count', 'opened_count', 'clicked_count', 'response_count'] as $metric) {
            $validated[$metric] = $validated[$metric] ?? 0;
        }

        $validated['marketing_campaign_id'] = $validated['marketing_campaign_id'] ?? null;
        $validated['audience_segment_id'] = $validated['audience_segment_id'] ?? null;

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function channelOptions(): array
    {
        return ['email', 'whatsapp', 'sms', 'social_media', 'ads'];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['scheduled', 'running', 'completed', 'failed', 'cancelled'];
    }

    /**
     * @return array<string, float>
     */
    protected function performanceRates(CampaignExecution $execution): array
    {
        return [
            'delivered_rate' => $this->rate((int) $execution->delivered_count, (int) $execution->sent_count),
            'open_rate' => $this->rate((int) $execution->opened_count, (int) $execution->delivered_count),
            'click_rate' => $this->rate((int) $execution->clicked_count, (int) $execution->opened_count),
            'response_rate' => $this->rate((int) $execution->response_count, (int) $execution->sent_count),
        ];
    }

    protected function rate(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0;
        }

        return round(($numerator / $denominator) * 100, 2);
    }
}
