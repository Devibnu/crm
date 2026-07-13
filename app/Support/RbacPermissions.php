<?php

namespace App\Support;

class RbacPermissions
{
    /** @return array<string, array<int, string>> */
    public static function groups(): array
    {
        return [
            'Customer Profile 360' => [
                'customers.view', 'customers.create', 'customers.update', 'customers.delete',
                'interactions.view', 'interactions.create', 'interactions.update', 'interactions.delete',
            ],
            'Sales Enablement' => [
                'leads.view', 'leads.create', 'leads.update', 'leads.delete',
                'opportunities.view', 'opportunities.create', 'opportunities.update', 'opportunities.delete',
                'pipeline.view',
                'activities.view', 'activities.create', 'activities.update', 'activities.delete',
                'quotations.view', 'quotations.create', 'quotations.update', 'quotations.delete',
                'winloss.view',
            ],
            'Project Management' => [
                'projects.view', 'projects.create', 'projects.update', 'projects.delete',
                'project.milestone.read', 'project.milestone.create', 'project.milestone.update', 'project.milestone.delete',
                'project.timeline.read', 'project.timeline.create', 'project.timeline.update',
                'project.timesheet.read', 'project.timesheet.create', 'project.timesheet.update', 'project.timesheet.delete', 'project.timesheet.approve',
                'project.report.read', 'project.report.export',
            ],
            'Service Management' => [
                'tickets.view', 'tickets.create', 'tickets.update', 'tickets.delete',
                'omnichannel.view', 'omnichannel.create', 'omnichannel.update', 'omnichannel.delete',
                'omnichannel_notes.view', 'omnichannel_notes.create', 'omnichannel_notes.update', 'omnichannel_notes.delete',
                'sla.view', 'sla.create', 'sla.update', 'sla.delete',
                'cases.view', 'cases.create', 'cases.update', 'cases.delete',
                'csat.view', 'csat.create', 'csat.update', 'csat.delete',
                'knowledge.view', 'knowledge.create', 'knowledge.update', 'knowledge.delete',
            ],
            'Marketing Automation' => [
                'campaigns.view', 'campaigns.create', 'campaigns.update', 'campaigns.delete',
                'audiences.view', 'audiences.create', 'audiences.update', 'audiences.delete',
                'executions.view', 'executions.create', 'executions.update', 'executions.delete',
                'landing_pages.view', 'landing_pages.create', 'landing_pages.update', 'landing_pages.delete',
                'social.view', 'social.create', 'social.update', 'social.delete',
                'automations.view', 'automations.create', 'automations.update', 'automations.delete',
                'lead_scoring.view', 'lead_scoring.create', 'lead_scoring.update', 'lead_scoring.delete',
            ],
            'WhatsApp Marketing' => [
                'whatsapp_providers.view', 'whatsapp_providers.create', 'whatsapp_providers.update', 'whatsapp_providers.delete',
                'whatsapp_cloud_api.view', 'whatsapp_cloud_api.create', 'whatsapp_cloud_api.update', 'whatsapp_cloud_api.delete',
                'whatsapp_templates.view', 'whatsapp_templates.create', 'whatsapp_templates.update', 'whatsapp_templates.delete',
                'whatsapp_broadcasts.view', 'whatsapp_broadcasts.create', 'whatsapp_broadcasts.update', 'whatsapp_broadcasts.delete',
                'whatsapp_replies.view', 'whatsapp_replies.create', 'whatsapp_replies.update', 'whatsapp_replies.delete',
            ],
            'System' => [
                'users.view', 'users.create', 'users.update', 'users.delete',
                'roles.view', 'roles.create', 'roles.update', 'roles.delete',
                'menus.view', 'menus.create', 'menus.update', 'menus.delete',
                'branding.view', 'branding.update',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function all(): array
    {
        return collect(self::groups())->flatten()->values()->all();
    }

    /** @return array<int, string> */
    public static function viewPermissions(): array
    {
        return array_values(array_filter(self::all(), fn (string $permission): bool => str_ends_with($permission, '.view')));
    }
}
