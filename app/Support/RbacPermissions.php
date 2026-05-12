<?php

namespace App\Support;

class RbacPermissions
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function groups(): array
    {
        return [
            'Customer Profile 360' => [
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
                'interactions.view',
                'interactions.create',
                'interactions.update',
                'interactions.delete',
            ],
            'Sales Enablement' => [
                'leads.view',
                'leads.create',
                'leads.update',
                'leads.delete',
                'opportunities.view',
                'opportunities.create',
                'opportunities.update',
                'opportunities.delete',
                'pipeline.view',
                'activities.view',
                'activities.create',
                'activities.update',
                'activities.delete',
                'quotations.view',
                'quotations.create',
                'quotations.update',
                'quotations.delete',
                'winloss.view',
            ],
            'Service Management' => [
                'tickets.view',
                'tickets.create',
                'tickets.update',
                'tickets.delete',
                'omnichannel.view',
                'omnichannel.create',
                'omnichannel.update',
                'omnichannel.delete',
                'sla.view',
                'cases.view',
                'csat.view',
                'knowledge.view',
                'knowledge.create',
                'knowledge.update',
                'knowledge.delete',
            ],
            'Marketing Automation' => [
                'campaigns.view',
                'campaigns.create',
                'campaigns.update',
                'campaigns.delete',
                'audiences.view',
                'audiences.create',
                'audiences.update',
                'audiences.delete',
                'executions.view',
                'executions.create',
                'executions.update',
                'executions.delete',
                'landing_pages.view',
                'landing_pages.create',
                'landing_pages.update',
                'landing_pages.delete',
                'social.view',
                'social.create',
                'social.update',
                'social.delete',
                'automations.view',
                'automations.create',
                'automations.update',
                'automations.delete',
                'lead_scoring.view',
                'lead_scoring.create',
                'lead_scoring.update',
                'lead_scoring.delete',
            ],
            'System' => [
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return collect(self::groups())->flatten()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public static function viewPermissions(): array
    {
        return array_values(array_filter(self::all(), fn (string $permission): bool => str_ends_with($permission, '.view')));
    }
}
