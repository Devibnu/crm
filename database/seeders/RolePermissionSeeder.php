<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\RbacPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RbacPermissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'super_admin' => RbacPermissions::all(),
            'admin' => array_values(array_diff(RbacPermissions::all(), ['users.delete', 'roles.delete'])),
            'manager' => array_values(array_unique(array_merge(
                RbacPermissions::viewPermissions(),
                [
                    'customers.update',
                    'leads.update',
                    'opportunities.update',
                    'pipeline.view',
                    'activities.update',
                    'quotations.update',
                    'tickets.update',
                    'winloss.view',
                ],
            ))),
            'sales' => [
                'customers.view',
                'customers.create',
                'customers.update',
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
            'marketing' => [
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
                'whatsapp_cloud_api.view',
                'whatsapp_cloud_api.create',
                'whatsapp_cloud_api.update',
                'whatsapp_cloud_api.delete',
                'whatsapp_templates.view',
                'whatsapp_templates.create',
                'whatsapp_templates.update',
                'whatsapp_templates.delete',
                'whatsapp_broadcasts.view',
                'whatsapp_broadcasts.create',
                'whatsapp_broadcasts.update',
                'whatsapp_broadcasts.delete',
                'whatsapp_replies.view',
                'whatsapp_replies.create',
                'whatsapp_replies.update',
                'whatsapp_replies.delete',
                'leads.view',
                'leads.create',
            ],
            'support' => [
                'tickets.view',
                'tickets.create',
                'tickets.update',
                'tickets.delete',
                'omnichannel.view',
                'omnichannel.create',
                'omnichannel.update',
                'omnichannel.delete',
                'omnichannel_notes.view',
                'omnichannel_notes.create',
                'omnichannel_notes.update',
                'omnichannel_notes.delete',
                'sla.view',
                'sla.create',
                'sla.update',
                'sla.delete',
                'cases.view',
                'cases.create',
                'cases.update',
                'cases.delete',
                'csat.view',
                'csat.create',
                'csat.update',
                'csat.delete',
                'knowledge.view',
                'knowledge.create',
                'knowledge.update',
                'knowledge.delete',
                'customers.view',
                'customers.update',
                'interactions.view',
                'interactions.create',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        User::query()->orderBy('id')->first()?->assignRole('super_admin');
        User::query()->where('email', 'test@example.com')->first()?->assignRole('super_admin');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
