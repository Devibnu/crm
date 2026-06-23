<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CustomerSeeder::class);
        $this->call(CustomerInteractionSeeder::class);
        $this->call(CustomerTransactionSeeder::class);
        $this->call(CustomerPreferenceSeeder::class);
        $this->call(CustomerBehaviorSeeder::class);
        $this->call(LeadSeeder::class);
        $this->call(OpportunitySeeder::class);
        $this->call(SalesActivitySeeder::class);
        $this->call(QuotationSeeder::class);
        $this->call(MarketingCampaignSeeder::class);
        $this->call(AudienceSegmentSeeder::class);
        $this->call(CampaignExecutionSeeder::class);
        $this->call(LandingPageSeeder::class);
        $this->call(SocialMediaEngagementSeeder::class);
        $this->call(MarketingAutomationSeeder::class);
        $this->call(WhatsAppProviderSeeder::class);
        $this->call(WhatsAppBroadcastSeeder::class);
        $this->call(WhatsAppBroadcastRecipientSeeder::class);
        $this->call(WhatsAppBroadcastReplySeeder::class);
        $this->call(LeadScoringRuleSeeder::class);
        $this->call(TicketSeeder::class);
        $this->call(CustomerSatisfactionSeeder::class);
        $this->call(CaseResolutionSeeder::class);
        $this->call(SlaPolicySeeder::class);
        $this->call(KnowledgeBaseSeeder::class);
        $this->call(OmnichannelMessageSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(RolePermissionSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(RoleMenuSeeder::class);
    }
}
