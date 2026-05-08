<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\MarketingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.landing-pages.index'))
            ->assertOk()
            ->assertSee('Landing Page & Form')
            ->assertSee('Total Landing Pages')
            ->assertSee('Published')
            ->assertSee('Draft')
            ->assertSee('Total Submissions');
    }

    public function test_landing_page_can_be_created(): void
    {
        $campaign = MarketingCampaign::factory()->create();

        $response = $this->post(route('admin.marketing.landing-pages.store'), $this->payload([
            'marketing_campaign_id' => $campaign->id,
            'title' => 'Lead Capture Landing Page',
            'slug' => 'lead-capture-landing-page',
        ]));

        $landingPage = LandingPage::query()->where('slug', 'lead-capture-landing-page')->firstOrFail();

        $response->assertRedirect(route('admin.marketing.landing-pages.show', $landingPage));
        $this->assertDatabaseHas('landing_pages', [
            'title' => 'Lead Capture Landing Page',
            'slug' => 'lead-capture-landing-page',
            'marketing_campaign_id' => $campaign->id,
        ]);
        $this->assertSame('full_name', $landingPage->fresh()->form_fields[0]['name']);
    }

    public function test_show_and_edit_pages_can_be_opened(): void
    {
        $landingPage = LandingPage::factory()->create(['title' => 'Show Edit Landing']);

        $this->get(route('admin.marketing.landing-pages.show', $landingPage))
            ->assertOk()
            ->assertSee('Show Edit Landing');

        $this->get(route('admin.marketing.landing-pages.edit', $landingPage))
            ->assertOk()
            ->assertSee('Edit Landing Page');
    }

    public function test_landing_page_can_be_updated(): void
    {
        $landingPage = LandingPage::factory()->create([
            'title' => 'Before Landing',
            'slug' => 'before-landing',
        ]);

        $response = $this->put(route('admin.marketing.landing-pages.update', $landingPage), $this->payload([
            'title' => 'After Landing',
            'slug' => 'after-landing',
            'status' => 'published',
            'form_fields' => '[{"name":"email","type":"email","required":true}]',
        ]));

        $response->assertRedirect(route('admin.marketing.landing-pages.show', $landingPage));
        $this->assertDatabaseHas('landing_pages', [
            'id' => $landingPage->id,
            'title' => 'After Landing',
            'slug' => 'after-landing',
            'status' => 'published',
        ]);
        $this->assertSame('email', $landingPage->fresh()->form_fields[0]['name']);
    }

    public function test_landing_page_can_be_deleted(): void
    {
        $landingPage = LandingPage::factory()->create();

        $response = $this->delete(route('admin.marketing.landing-pages.destroy', $landingPage));

        $response->assertRedirect(route('admin.marketing.landing-pages.index'));
        $this->assertDatabaseMissing('landing_pages', ['id' => $landingPage->id]);
    }

    public function test_search_works(): void
    {
        $match = LandingPage::factory()->create([
            'title' => 'Searchable Lead Magnet',
            'headline' => 'Exclusive CRM benchmark',
        ]);
        $other = LandingPage::factory()->create(['title' => 'Other Landing Page']);

        $this->get(route('admin.marketing.landing-pages.index', ['q' => 'CRM benchmark']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee($other->title);
    }

    public function test_filter_status_works(): void
    {
        $published = LandingPage::factory()->create(['title' => 'Published Landing Filter', 'status' => 'published']);
        $draft = LandingPage::factory()->create(['title' => 'Draft Landing Filter', 'status' => 'draft']);

        $this->get(route('admin.marketing.landing-pages.index', ['status' => 'published']))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);
    }

    public function test_form_fields_are_displayed(): void
    {
        $landingPage = LandingPage::factory()->create([
            'title' => 'Form Fields Landing',
            'form_fields' => [
                ['name' => 'full_name', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'type' => 'email', 'required' => true],
                ['name' => 'phone', 'type' => 'text', 'required' => false],
            ],
        ]);

        $this->get(route('admin.marketing.landing-pages.show', $landingPage))
            ->assertOk()
            ->assertSee('Form Fields Preview')
            ->assertSee('Full Name')
            ->assertSee('Email')
            ->assertSee('Phone')
            ->assertSee('full_name');
    }

    public function test_conversion_rate_is_displayed_correctly(): void
    {
        $landingPage = LandingPage::factory()->create([
            'title' => 'Conversion Landing',
            'views_count' => 1000,
            'submissions_count' => 125,
        ]);

        $this->get(route('admin.marketing.landing-pages.show', $landingPage))
            ->assertOk()
            ->assertSee('Conversion Rate')
            ->assertSee('12.50%');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'marketing_campaign_id' => null,
            'title' => 'Default Landing Page',
            'slug' => 'default-landing-page',
            'headline' => 'Default landing headline',
            'subheadline' => 'Default landing subheadline.',
            'form_fields' => '[{"name":"full_name","type":"text","required":true},{"name":"email","type":"email","required":true},{"name":"phone","type":"text","required":false}]',
            'thank_you_message' => 'Terima kasih, kami akan segera menghubungi Anda.',
            'status' => 'draft',
            'views_count' => 100,
            'submissions_count' => 10,
            'published_at' => '2026-05-10 09:00:00',
            'created_by' => 'Marketing Ops',
        ], $overrides);
    }
}
