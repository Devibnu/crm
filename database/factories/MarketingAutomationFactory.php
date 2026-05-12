<?php

namespace Database\Factories;

use App\Models\AudienceSegment;
use App\Models\MarketingAutomation;
use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingAutomation>
 */
class MarketingAutomationFactory extends Factory
{
    protected $model = MarketingAutomation::class;

    public function definition(): array
    {
        $trigger = fake()->randomElement(['form_submit', 'lead_created', 'campaign_opened', 'link_clicked', 'manual']);
        $action = fake()->randomElement(['send_email', 'send_whatsapp', 'assign_sales', 'add_to_segment', 'create_task']);
        $status = fake()->randomElement(['draft', 'active', 'paused', 'completed']);
        $executed = $status === 'draft' ? 0 : fake()->numberBetween(3, 1200);

        return [
            'marketing_campaign_id' => fake()->boolean(70) ? MarketingCampaign::query()->inRandomOrder()->value('id') : null,
            'audience_segment_id' => fake()->boolean(70) ? AudienceSegment::query()->inRandomOrder()->value('id') : null,
            'name' => fake()->randomElement([
                'Welcome Nurture Flow',
                'Lead Qualification Rule',
                'Reactivation Sequence',
                'Campaign Follow-up',
                'Sales Handoff Automation',
                'Webinar Reminder Flow',
            ]) . ' ' . fake()->bothify('##??'),
            'trigger_type' => $trigger,
            'action_type' => $action,
            'status' => $status,
            'delay_minutes' => fake()->randomElement([0, 15, 30, 60, 180, 360, 1440]),
            'conditions' => $this->conditionsFor($trigger),
            'action_payload' => $this->payloadFor($action),
            'executed_count' => $executed,
            'last_executed_at' => $executed > 0 ? fake()->dateTimeBetween('-4 months', 'now')->format('Y-m-d H:i:s') : null,
            'created_by' => fake()->randomElement(['Marketing Ops', 'CRM Admin', 'Growth Team', 'Sales Enablement']),
            'notes' => fake()->optional()->sentence(14),
        ];
    }

    protected function conditionsFor(string $trigger): array
    {
        return match ($trigger) {
            'form_submit' => [
                'form' => fake()->randomElement(['lead_capture', 'webinar_registration', 'demo_request']),
                'lead_score' => ['>=' => fake()->randomElement([30, 50, 70])],
            ],
            'lead_created' => [
                'source' => fake()->randomElement(['website', 'event', 'ads', 'referral']),
                'region' => fake()->randomElement(['Jabodetabek', 'West Java', 'East Java', 'Sumatra']),
            ],
            'campaign_opened' => [
                'campaign_type' => fake()->randomElement(['email', 'whatsapp', 'webinar']),
                'minimum_open_count' => fake()->numberBetween(1, 4),
            ],
            'link_clicked' => [
                'link_type' => fake()->randomElement(['pricing', 'case_study', 'product_page']),
                'clicked_within_hours' => fake()->randomElement([24, 48, 72]),
            ],
            default => [
                'manual_reason' => fake()->randomElement(['high_value_account', 'sales_request', 'quarterly_push']),
            ],
        };
    }

    protected function payloadFor(string $action): array
    {
        return match ($action) {
            'send_email' => [
                'template' => fake()->randomElement(['welcome_email', 'case_study_followup', 'demo_invitation']),
                'subject' => fake()->sentence(5),
            ],
            'send_whatsapp' => [
                'template' => fake()->randomElement(['wa_intro', 'wa_reminder', 'wa_follow_up']),
                'sender' => fake()->randomElement(['Marketing Bot', 'CRM Assistant']),
            ],
            'assign_sales' => [
                'team' => fake()->randomElement(['Enterprise Sales', 'SMB Sales', 'Inside Sales']),
                'priority' => fake()->randomElement(['normal', 'high', 'urgent']),
            ],
            'add_to_segment' => [
                'segment_rule' => fake()->randomElement(['engaged_leads', 'hot_accounts', 'webinar_attendees']),
                'ttl_days' => fake()->randomElement([30, 60, 90]),
            ],
            default => [
                'task_title' => fake()->randomElement(['Call lead', 'Send proposal', 'Review account']),
                'due_in_days' => fake()->randomElement([1, 2, 3, 7]),
            ],
        };
    }
}
