<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $landingPages = LandingPage::query()
            ->with('marketingCampaign:id,name')
            ->search($search)
            ->filterStatus($status, $this->statusOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => LandingPage::query()->count(),
            'published' => LandingPage::query()->where('status', 'published')->count(),
            'draft' => LandingPage::query()->where('status', 'draft')->count(),
            'submissions' => LandingPage::query()->sum('submissions_count'),
        ];

        return view('admin.marketing.landing-pages.index', [
            'landingPages' => $landingPages,
            'search' => $search,
            'selectedStatus' => $status,
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.marketing.landing-pages.create', [
            'landingPage' => null,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'formFieldsJson' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $landingPage = LandingPage::create($this->validatedData($request));

        return redirect()
            ->route('admin.marketing.landing-pages.show', $landingPage)
            ->with('success', 'Landing page berhasil ditambahkan.');
    }

    public function show(LandingPage $landingPage): View
    {
        return view('admin.marketing.landing-pages.show', [
            'landingPage' => $landingPage->load('marketingCampaign:id,name'),
            'conversionRate' => $this->conversionRate($landingPage),
            'formFieldsJson' => $this->prettyFormFields($landingPage),
        ]);
    }

    public function edit(LandingPage $landingPage): View
    {
        return view('admin.marketing.landing-pages.edit', [
            'landingPage' => $landingPage,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'formFieldsJson' => $this->prettyFormFields($landingPage),
        ]);
    }

    public function update(Request $request, LandingPage $landingPage): RedirectResponse
    {
        $landingPage->update($this->validatedData($request, $landingPage));

        return redirect()
            ->route('admin.marketing.landing-pages.show', $landingPage)
            ->with('success', 'Landing page berhasil diperbarui.');
    }

    public function destroy(LandingPage $landingPage): RedirectResponse
    {
        $landingPage->delete();

        return redirect()
            ->route('admin.marketing.landing-pages.index')
            ->with('success', 'Landing page berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request, ?LandingPage $landingPage = null): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('landing_pages', 'slug')->ignore($landingPage?->id)],
            'headline' => ['nullable', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string'],
            'form_fields' => ['nullable', 'json'],
            'thank_you_message' => ['nullable', 'string'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'views_count' => ['nullable', 'integer', 'min:0'],
            'submissions_count' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        $formFields = trim((string) ($validated['form_fields'] ?? ''));
        $validated['form_fields'] = $formFields === '' ? null : json_decode($formFields, true);
        $validated['marketing_campaign_id'] = $validated['marketing_campaign_id'] ?? null;
        $validated['views_count'] = $validated['views_count'] ?? 0;
        $validated['submissions_count'] = $validated['submissions_count'] ?? 0;

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['draft', 'published', 'archived'];
    }

    protected function conversionRate(LandingPage $landingPage): float
    {
        if ((int) $landingPage->views_count <= 0) {
            return 0;
        }

        return round(((int) $landingPage->submissions_count / (int) $landingPage->views_count) * 100, 2);
    }

    protected function prettyFormFields(LandingPage $landingPage): string
    {
        if ($landingPage->form_fields === null || $landingPage->form_fields === []) {
            return '';
        }

        return json_encode($landingPage->form_fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
