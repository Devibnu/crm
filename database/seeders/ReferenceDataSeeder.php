<?php

namespace Database\Seeders;

use App\Models\ReferenceType;
use App\Models\ReferenceValue;
use App\Models\ReferenceValueCapability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedServiceChannels();
    }

    protected function seedServiceChannels(): void
    {
        $type = ReferenceType::updateOrCreate(
            ['code' => 'service_channel'],
            [
                'name' => 'Service Channels',
                'description' => 'Reusable channel codes for service, satisfaction, campaign, and omnichannel contexts.',
                'governance_level' => 4,
                'is_active' => true,
                'metadata' => null,
            ],
        );

        foreach ($this->serviceChannels() as $sortOrder => $channel) {
            $value = ReferenceValue::updateOrCreate(
                [
                    'reference_type_id' => $type->id,
                    'code' => $channel['code'],
                ],
                [
                    'label' => $channel['label'],
                    'description' => $channel['description'] ?? null,
                    'color' => $channel['color'] ?? null,
                    'icon' => $channel['icon'] ?? null,
                    'sort_order' => $sortOrder + 1,
                    'is_active' => true,
                    'is_system' => true,
                    'is_default' => $channel['code'] === 'web',
                    'metadata' => $channel['metadata'] ?? null,
                ],
            );

            $existing = $value->capabilities()->pluck('capability')->all();

            foreach ($channel['capabilities'] as $capability) {
                ReferenceValueCapability::updateOrCreate([
                    'reference_value_id' => $value->id,
                    'capability' => $capability,
                ]);
            }

            $value->capabilities()
                ->whereNotIn('capability', $channel['capabilities'])
                ->whereIn('capability', $existing)
                ->delete();
        }

        $this->forgetServiceChannelCache();
    }

    /**
     * @return array<int, array{code: string, label: string, capabilities: array<int, string>, description?: string, color?: string, icon?: string, metadata?: array<string, mixed>}>
     */
    protected function serviceChannels(): array
    {
        return [
            [
                'code' => 'email',
                'label' => 'Email',
                'capabilities' => ['service_ticket', 'csat_survey', 'customer_preference', 'campaign_execution', 'omnichannel_inbox', 'outbound_broadcast', 'supports_inbound', 'supports_outbound', 'supports_template', 'supports_attachment'],
            ],
            [
                'code' => 'whatsapp',
                'label' => 'WhatsApp',
                'capabilities' => ['service_ticket', 'csat_survey', 'customer_preference', 'campaign_execution', 'omnichannel_inbox', 'outbound_broadcast', 'supports_inbound', 'supports_outbound', 'supports_template', 'supports_attachment', 'requires_provider'],
            ],
            [
                'code' => 'phone',
                'label' => 'Phone',
                'capabilities' => ['service_ticket', 'csat_survey', 'customer_preference', 'supports_inbound', 'supports_outbound'],
            ],
            [
                'code' => 'web',
                'label' => 'Web',
                'capabilities' => ['service_ticket', 'csat_survey', 'campaign_execution', 'supports_inbound'],
            ],
            [
                'code' => 'sms',
                'label' => 'SMS',
                'capabilities' => ['campaign_execution', 'outbound_broadcast', 'supports_outbound', 'requires_provider'],
            ],
            [
                'code' => 'social',
                'label' => 'Social',
                'capabilities' => ['service_ticket'],
                'metadata' => ['note' => 'Kept separate from social_media for backward compatibility.'],
            ],
            [
                'code' => 'social_media',
                'label' => 'Social Media',
                'capabilities' => ['campaign_execution'],
                'metadata' => ['note' => 'Kept separate from social for backward compatibility.'],
            ],
            [
                'code' => 'walk_in',
                'label' => 'Walk-in',
                'capabilities' => ['service_ticket'],
            ],
            [
                'code' => 'livechat',
                'label' => 'Live Chat',
                'capabilities' => ['omnichannel_inbox', 'supports_inbound', 'supports_outbound'],
            ],
            [
                'code' => 'facebook',
                'label' => 'Facebook',
                'capabilities' => ['omnichannel_inbox', 'campaign_execution', 'supports_inbound', 'supports_outbound', 'requires_provider'],
            ],
            [
                'code' => 'instagram',
                'label' => 'Instagram',
                'capabilities' => ['omnichannel_inbox', 'campaign_execution', 'supports_inbound', 'supports_outbound', 'requires_provider'],
            ],
            [
                'code' => 'telegram',
                'label' => 'Telegram',
                'capabilities' => ['omnichannel_inbox', 'supports_inbound', 'supports_outbound', 'requires_provider'],
            ],
            [
                'code' => 'meeting',
                'label' => 'Meeting',
                'capabilities' => ['customer_preference'],
            ],
            [
                'code' => 'ads',
                'label' => 'Ads',
                'capabilities' => ['campaign_execution'],
            ],
            [
                'code' => 'none',
                'label' => 'None',
                'capabilities' => ['customer_preference'],
            ],
        ];
    }

    protected function forgetServiceChannelCache(): void
    {
        $capabilities = collect($this->serviceChannels())
            ->flatMap(fn (array $channel): array => $channel['capabilities'])
            ->push('all')
            ->unique()
            ->values();

        foreach ($capabilities as $capability) {
            foreach (['active', 'all'] as $activeState) {
                Cache::forget(sprintf('reference_data.options.service_channel.%s.%s', $capability, $activeState));
            }
        }
    }
}
