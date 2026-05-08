<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));

        $campaigns = MarketingCampaign::query()
            ->search($search)
            ->filterType($type, $this->typeOptions())
            ->filterStatus($status, $this->statusOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => MarketingCampaign::query()->count(),
            'running' => MarketingCampaign::query()->where('status', 'running')->count(),
            'completed' => MarketingCampaign::query()->where('status', 'completed')->count(),
            'total_leads' => MarketingCampaign::query()->sum('actual_leads'),
        ];

        return view('admin.marketing.campaigns.index', [
            'campaigns' => $campaigns,
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
        return view('admin.marketing.campaigns.create', [
            'campaign' => null,
            'typeOptions' => $this->typeOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $campaign = MarketingCampaign::create($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.campaigns.show', $campaign)
            ->with('success', 'Campaign berhasil ditambahkan.');
    }

    public function show(MarketingCampaign $campaign): View
    {
        return view('admin.marketing.campaigns.show', [
            'campaign' => $campaign,
            'progress' => $this->leadProgress($campaign),
        ]);
    }

    public function edit(MarketingCampaign $campaign): View
    {
        return view('admin.marketing.campaigns.edit', [
            'campaign' => $campaign,
            'typeOptions' => $this->typeOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, MarketingCampaign $campaign): RedirectResponse
    {
        $campaign->update($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.campaigns.show', $campaign)
            ->with('success', 'Campaign berhasil diperbarui.');
    }

    public function destroy(MarketingCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()
            ->route('admin.marketing.campaigns.index')
            ->with('success', 'Campaign berhasil dihapus.');
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
            'target_audience' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'expected_leads' => ['nullable', 'integer', 'min:0'],
            'actual_leads' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['expected_leads'] = $validated['expected_leads'] ?? 0;
        $validated['actual_leads'] = $validated['actual_leads'] ?? 0;

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function typeOptions(): array
    {
        return ['email', 'whatsapp', 'social_media', 'webinar', 'event', 'ads'];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['draft', 'scheduled', 'running', 'completed', 'cancelled'];
    }

    protected function leadProgress(MarketingCampaign $campaign): float
    {
        if ((int) $campaign->expected_leads <= 0) {
            return 0;
        }

        return round(((int) $campaign->actual_leads / (int) $campaign->expected_leads) * 100, 2);
    }
}
