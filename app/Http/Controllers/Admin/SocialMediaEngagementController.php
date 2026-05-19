<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\SocialMediaEngagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SocialMediaEngagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $platform = trim((string) $request->query('platform', ''));
        $status = trim((string) $request->query('status', ''));

        $posts = SocialMediaEngagement::query()
            ->with('marketingCampaign:id,name')
            ->search($search)
            ->filterPlatform($platform, $this->platformOptions())
            ->filterStatus($status, $this->statusOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => SocialMediaEngagement::query()->count(),
            'published' => SocialMediaEngagement::query()->where('status', 'published')->count(),
            'impressions' => SocialMediaEngagement::query()->sum('impressions_count'),
            'average_engagement_rate' => (float) SocialMediaEngagement::query()->avg('engagement_rate'),
        ];

        return view('admin.marketing.social-engagements.index', [
            'posts' => $posts,
            'search' => $search,
            'selectedPlatform' => $platform,
            'selectedStatus' => $status,
            'platformOptions' => $this->platformOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.marketing.social-engagements.create', [
            'post' => null,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'platformOptions' => $this->platformOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $post = SocialMediaEngagement::create($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.social-engagements.show', $post)
            ->with('success', 'Social media post berhasil ditambahkan.');
    }

    public function show(SocialMediaEngagement $socialEngagement): View
    {
        return view('admin.marketing.social-engagements.show', [
            'post' => $socialEngagement->load('marketingCampaign:id,name'),
            'engagementScore' => $this->engagementScore($socialEngagement),
        ]);
    }

    public function edit(SocialMediaEngagement $socialEngagement): View
    {
        return view('admin.marketing.social-engagements.edit', [
            'post' => $socialEngagement,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'platformOptions' => $this->platformOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, SocialMediaEngagement $socialEngagement): RedirectResponse
    {
        $socialEngagement->update($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.social-engagements.show', $socialEngagement)
            ->with('success', 'Social media post berhasil diperbarui.');
    }

    public function destroy(SocialMediaEngagement $socialEngagement): RedirectResponse
    {
        $socialEngagement->delete();

        return redirect()
            ->route('admin.marketing.social-engagements.index')
            ->with('success', 'Social media post berhasil dihapus.');
    }

    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'platform' => ['required', Rule::in($this->platformOptions())],
            'post_title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'post_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'posted_at' => ['nullable', 'date'],
            'likes_count' => ['nullable', 'integer', 'min:0'],
            'comments_count' => ['nullable', 'integer', 'min:0'],
            'shares_count' => ['nullable', 'integer', 'min:0'],
            'impressions_count' => ['nullable', 'integer', 'min:0'],
            'engagement_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        foreach (['likes_count', 'comments_count', 'shares_count', 'impressions_count'] as $metric) {
            $validated[$metric] = $validated[$metric] ?? 0;
        }

        $validated['engagement_rate'] = $validated['engagement_rate'] ?? 0;
        $validated['marketing_campaign_id'] = $validated['marketing_campaign_id'] ?? null;

        return $validated;
    }

    protected function platformOptions(): array
    {
        return ['instagram', 'facebook', 'linkedin', 'twitter', 'tiktok', 'youtube'];
    }

    protected function statusOptions(): array
    {
        return ['draft', 'scheduled', 'published', 'archived'];
    }

    protected function engagementScore(SocialMediaEngagement $post): float
    {
        if ((int) $post->impressions_count <= 0) {
            return 0;
        }

        $interactions = (int) $post->likes_count + (int) $post->comments_count + (int) $post->shares_count;

        return round(($interactions / (int) $post->impressions_count) * 100, 2);
    }
}
