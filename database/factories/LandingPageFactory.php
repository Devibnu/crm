<?php

namespace Database\Factories;

use App\Models\LandingPage;
use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LandingPage>
 */
class LandingPageFactory extends Factory
{
    protected $model = LandingPage::class;

    public function definition(): array
    {
        $title = fake()->randomElement([
            'Free CRM Consultation',
            'Marketing Automation Demo',
            'Webinar Registration',
            'ROI Calculator Download',
            'Enterprise CRM Trial',
            'Customer Engagement Guide',
        ]) . ' ' . fake()->unique()->numberBetween(100, 9999);
        $views = fake()->numberBetween(100, 50000);
        $submissions = fake()->numberBetween(0, (int) floor($views * 0.35));
        $status = fake()->randomElement(['draft', 'published', 'archived']);

        return [
            'marketing_campaign_id' => fake()->boolean(70) ? MarketingCampaign::query()->inRandomOrder()->value('id') : null,
            'title' => $title,
            'slug' => Str::slug($title),
            'headline' => fake()->randomElement([
                'Grow your pipeline with Krakatau CRM',
                'Turn campaign traffic into qualified leads',
                'Build better customer engagement',
                'Capture high-intent prospects faster',
            ]),
            'subheadline' => fake()->sentence(16),
            'form_fields' => [
                ['name' => 'full_name', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'type' => 'email', 'required' => true],
                ['name' => 'phone', 'type' => 'text', 'required' => fake()->boolean()],
                ['name' => 'company', 'type' => 'text', 'required' => fake()->boolean(65)],
            ],
            'thank_you_message' => 'Terima kasih, tim kami akan menghubungi Anda segera.',
            'status' => $status,
            'views_count' => $views,
            'submissions_count' => $submissions,
            'published_at' => $status === 'published' ? fake()->dateTimeBetween('-4 months', 'now')->format('Y-m-d H:i:s') : null,
            'created_by' => fake()->randomElement(['Marketing Ops', 'Growth Team', 'Demand Generation', 'CRM Marketing']),
        ];
    }
}
