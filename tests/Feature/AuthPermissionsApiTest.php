<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Pelanggan;
use App\Models\Pesan;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthPermissionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat_demo_profiles_are_seeded_with_expected_module_permissions(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@demo.com',
            'role' => 'admin',
        ]);

        $this->assertSame([
            'customers' => 'view',
            'tickets' => 'view',
            'inbox' => 'view',
            'whatsapp' => 'view',
            'invoice' => 'view',
        ], User::query()->where('email', 'observer@demo.com')->firstOrFail()->resolvedModulePermissions());

        $this->assertSame([
            'customers' => 'view',
            'tickets' => 'handle',
            'inbox' => 'handle',
            'whatsapp' => 'handle',
            'invoice' => 'view',
        ], User::query()->where('email', 'inbox-operator@demo.com')->firstOrFail()->resolvedModulePermissions());

        $this->assertSame([
            'customers' => 'manage',
            'tickets' => 'view',
            'inbox' => 'view',
            'whatsapp' => 'view',
            'invoice' => 'view',
        ], User::query()->where('email', 'customer-admin@demo.com')->firstOrFail()->resolvedModulePermissions());

        $this->assertSame([
            'customers' => 'view',
            'tickets' => 'view',
            'inbox' => 'view',
            'whatsapp' => 'view',
            'invoice' => 'manage',
        ], User::query()->where('email', 'finance-admin@demo.com')->firstOrFail()->resolvedModulePermissions());

        $this->assertSame(User::defaultModulePermissionsForRole('marketing'), User::query()->where('email', 'marketing@demo.com')->firstOrFail()->resolvedModulePermissions());
        $this->assertSame(User::defaultModulePermissionsForRole('sales'), User::query()->where('email', 'sales@demo.com')->firstOrFail()->resolvedModulePermissions());
        $this->assertSame(User::defaultModulePermissionsForRole('service'), User::query()->where('email', 'service@demo.com')->firstOrFail()->resolvedModulePermissions());
    }

    public function test_user_seeder_keeps_existing_non_demo_users(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'full_name' => 'Existing User',
        ]);

        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'existing@example.com',
            'full_name' => 'Existing User',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@demo.com',
        ]);
    }

    public function test_login_returns_module_permissions_and_crm_ability_rules(): void
    {
        User::query()->create([
            'full_name' => 'Auth Admin',
            'username' => 'authadmin',
            'email' => 'auth-admin@example.com',
            'password' => 'secret123',
            'role' => 'admin',
            'module_permissions' => [
                'customers' => 'full',
                'tickets' => 'manage',
                'inbox' => 'handle',
                'whatsapp' => 'view',
                'invoice' => 'manage',
            ],
            'current_plan' => 'enterprise',
            'status' => 'active',
            'billing' => 'Auto Debit',
            'language' => 'English',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'auth-admin@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('userData.email', 'auth-admin@example.com')
            ->assertJsonPath('userData.modulePermissions.customers', 'full')
            ->assertJsonPath('userData.modulePermissions.inbox', 'handle')
            ->assertJsonFragment(['action' => 'manage', 'subject' => 'Admin'])
            ->assertJsonFragment(['action' => 'manage', 'subject' => 'CrmCustomers'])
            ->assertJsonFragment(['action' => 'read', 'subject' => 'CrmCustomers'])
            ->assertJsonFragment(['action' => 'create', 'subject' => 'CrmTickets'])
            ->assertJsonFragment(['action' => 'update', 'subject' => 'CrmInbox'])
            ->assertJsonFragment(['action' => 'read', 'subject' => 'CrmWhatsapp'])
            ->assertJsonFragment(['action' => 'create', 'subject' => 'BackofficeInvoice']);
    }

    public function test_marketing_sales_and_service_demo_accounts_can_read_customers_but_cannot_write_customer_data(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);

        Pelanggan::query()->create([
            'nama' => 'CDP Customer',
            'email' => 'cdp@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        foreach ([
            ['email' => 'marketing@demo.com', 'password' => 'marketing'],
            ['email' => 'sales@demo.com', 'password' => 'sales'],
            ['email' => 'service@demo.com', 'password' => 'service'],
        ] as $credentials) {
            $loginResponse = $this->postJson('/api/auth/login', $credentials);

            $loginResponse
                ->assertCreated()
                ->assertJsonFragment(['action' => 'read', 'subject' => 'CrmCustomers']);

            $user = User::query()->where('email', $credentials['email'])->firstOrFail();

            $this->assertTrue($user->canAccess('read', 'CrmCustomers'));
            $this->assertFalse($user->canAccess('create', 'CrmCustomers'));

            Sanctum::actingAs($user);

            $this->getJson('/api/crm/pelanggan')->assertOk();
            $this->postJson('/api/crm/pelanggan', [
                'nama' => 'Forbidden Customer',
                'email' => 'forbidden-cdp@example.com',
                'status' => 'active',
                'source' => 'manual',
            ])->assertForbidden();
        }
    }

    public function test_me_returns_wrapped_user_data_and_ability_rules(): void
    {
        $user = User::factory()->create([
            'role' => 'maintainer',
            'module_permissions' => [
                'customers' => 'manage',
                'tickets' => 'manage',
                'inbox' => 'manage',
                'whatsapp' => 'manage',
                'invoice' => 'manage',
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('userData.id', $user->id)
            ->assertJsonPath('userData.role', 'maintainer')
            ->assertJsonPath('userData.modulePermissions.whatsapp', 'manage')
            ->assertJsonPath('userData.modulePermissions.invoice', 'manage')
            ->assertJsonFragment(['action' => 'create', 'subject' => 'CrmCustomers'])
            ->assertJsonFragment(['action' => 'delete', 'subject' => 'CrmInbox'])
            ->assertJsonFragment(['action' => 'delete', 'subject' => 'BackofficeInvoice']);
    }

    public function test_admin_can_update_user_module_permissions_and_me_reflects_latest_values(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'module_permissions' => User::defaultModulePermissionsForRole('admin'),
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/apps/users/{$admin->id}", [
            'role' => 'admin',
            'modulePermissions' => [
                'customers' => 'manage',
                'tickets' => 'manage',
                'inbox' => 'handle',
                'whatsapp' => 'view',
                'invoice' => 'manage',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('modulePermissions.inbox', 'handle')
            ->assertJsonPath('modulePermissions.invoice', 'manage');

        $meResponse = $this->getJson('/api/auth/me');
        $abilityRules = collect($meResponse->json('userAbilityRules'));

        $meResponse
            ->assertOk()
            ->assertJsonPath('userData.modulePermissions.inbox', 'handle')
            ->assertJsonPath('userData.modulePermissions.whatsapp', 'view')
            ->assertJsonPath('userData.modulePermissions.invoice', 'manage');

        $this->assertTrue($abilityRules->contains(fn (array $rule) => $rule['action'] === 'update' && $rule['subject'] === 'CrmInbox'));
        $this->assertFalse($abilityRules->contains(fn (array $rule) => $rule['action'] === 'create' && $rule['subject'] === 'CrmInbox'));
        $this->assertTrue($abilityRules->contains(fn (array $rule) => $rule['action'] === 'create' && $rule['subject'] === 'BackofficeInvoice'));
    }

    public function test_read_only_user_cannot_create_customers_or_manage_users(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/crm/pelanggan', [
            'nama' => 'Forbidden Customer',
            'email' => 'forbidden@example.com',
        ])->assertForbidden();

        $this->getJson('/api/apps/users')->assertForbidden();
    }

    public function test_user_without_whatsapp_access_cannot_open_whatsapp_api(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => null,
                'invoice' => 'view',
            ],
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/crm/inbox/whatsapp')->assertForbidden();
    }

    public function test_dashboard_hides_unauthorized_metrics_and_recent_tickets(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => null,
                'inbox' => null,
                'whatsapp' => null,
                'invoice' => 'view',
            ],
        ]);

        Pelanggan::query()->create([
            'nama' => 'Dashboard Customer',
            'email' => 'dashboard@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => 1,
            'kategori' => 'general',
            'subjek' => 'Hidden ticket',
            'status' => 'baru',
            'prioritas' => 'sedang',
        ]);

        Pesan::query()->create([
            'tiket_id' => $ticket->id,
            'channel' => 'whatsapp',
            'isi_pesan' => 'Hidden conversation',
            'pengirim' => 'Dashboard Customer',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/crm/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.pelanggan', 1)
            ->assertJsonPath('stats.tiket', 0)
            ->assertJsonPath('stats.percakapan', 0)
            ->assertJsonPath('stats.pengguna', 0)
            ->assertJsonCount(0, 'recentTickets');
    }

    public function test_read_only_user_cannot_update_email_flags(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/apps/email', [
            'ids' => [900001],
            'data' => ['isRead' => true],
        ])->assertForbidden();
    }

    public function test_read_only_user_cannot_list_or_create_tickets(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => null,
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ]);

        $customer = Pelanggan::query()->create([
            'nama' => 'Ticket Customer',
            'email' => 'ticket@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/crm/tiket')->assertForbidden();

        $this->postJson('/api/crm/tiket', [
            'pelangganId' => $customer->id,
            'kategori' => 'general',
            'prioritas' => 'sedang',
            'isiPesan' => 'Tidak boleh membuat tiket',
        ])->assertForbidden();
    }

    public function test_read_only_inbox_user_cannot_open_inbox_overview_or_reply(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => null,
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ]);

        $customer = Pelanggan::query()->create([
            'nama' => 'Inbox Customer',
            'email' => 'inbox@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'kategori' => 'general',
            'subjek' => 'Inbox ticket',
            'status' => 'baru',
            'prioritas' => 'sedang',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/crm/inbox/overview')->assertForbidden();

        $this->postJson("/api/crm/inbox/conversations/{$ticket->id}/reply", [
            'isiPesan' => 'Tidak boleh balas',
            'mode' => 'internal',
        ])->assertForbidden();
    }

    public function test_non_admin_user_cannot_update_other_users(): void
    {
        $user = User::factory()->create([
            'role' => 'maintainer',
            'module_permissions' => [
                'customers' => 'manage',
                'tickets' => 'manage',
                'inbox' => 'manage',
                'whatsapp' => 'manage',
                'invoice' => 'manage',
            ],
        ]);

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $this->putJson("/api/apps/users/{$otherUser->id}", [
            'role' => 'subscriber',
            'modulePermissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ])->assertForbidden();
    }

    public function test_inbox_operator_can_handle_inbox_and_tickets_but_cannot_modify_customer_profiles(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'handle',
                'inbox' => 'handle',
                'whatsapp' => 'handle',
                'invoice' => 'view',
            ],
        ]);

        $customer = Pelanggan::query()->create([
            'nama' => 'Operator Customer',
            'email' => 'operator@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'kategori' => 'general',
            'subjek' => 'Operator ticket',
            'status' => 'baru',
            'prioritas' => 'sedang',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/crm/inbox/overview')->assertOk();

        $this->postJson("/api/crm/inbox/conversations/{$ticket->id}/reply", [
            'isiPesan' => 'Balasan operator',
            'mode' => 'internal',
            'status' => 'diproses',
        ])->assertOk();

        $this->patchJson("/api/crm/tiket/{$ticket->id}/assign", [
            'assignedUserId' => $user->id,
        ])->assertOk();

        $this->patchJson("/api/crm/tiket/{$ticket->id}/status", [
            'status' => 'diproses',
        ])->assertOk();

        $this->putJson("/api/crm/pelanggan/{$customer->id}", [
            'nama' => 'Updated Operator Customer',
            'email' => 'operator@example.com',
            'status' => 'active',
            'source' => 'manual',
        ])->assertForbidden();

        $this->postJson("/api/crm/pelanggan/{$customer->id}/identities", [
            'type' => 'email',
            'value' => 'baru-operator@example.com',
            'label' => 'Sekunder',
            'isPrimary' => false,
            'isVerified' => false,
        ])->assertForbidden();
    }

    public function test_customer_admin_can_manage_customer_profiles_but_cannot_handle_ticket_or_inbox_updates(): void
    {
        $user = User::factory()->create([
            'role' => 'maintainer',
            'module_permissions' => [
                'customers' => 'manage',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ]);

        $customer = Pelanggan::query()->create([
            'nama' => 'Customer Admin Seed',
            'email' => 'customer-admin-seed@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'kategori' => 'general',
            'subjek' => 'Customer admin ticket',
            'status' => 'baru',
            'prioritas' => 'sedang',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/crm/pelanggan', [
            'nama' => 'Managed Customer',
            'email' => 'managed@example.com',
            'status' => 'active',
            'source' => 'manual',
        ])->assertCreated();

        $this->putJson("/api/crm/pelanggan/{$customer->id}", [
            'nama' => 'Managed Existing Customer',
            'email' => 'customer-admin-seed@example.com',
            'status' => 'active',
            'source' => 'manual',
        ])->assertOk();

        $this->postJson("/api/crm/pelanggan/{$customer->id}/identities", [
            'type' => 'whatsapp',
            'value' => '6281234567890',
            'label' => 'Primary WA',
            'isPrimary' => true,
            'isVerified' => true,
        ])->assertCreated();

        $this->getJson('/api/crm/tiket')->assertOk();

        $this->postJson('/api/crm/tiket', [
            'pelangganId' => $customer->id,
            'kategori' => 'general',
            'prioritas' => 'sedang',
            'isiPesan' => 'Tidak boleh membuat tiket',
        ])->assertForbidden();

        $this->patchJson("/api/crm/tiket/{$ticket->id}/assign", [
            'assignedUserId' => $user->id,
        ])->assertForbidden();

        $this->postJson("/api/crm/inbox/conversations/{$ticket->id}/reply", [
            'isiPesan' => 'Tidak boleh balas inbox',
            'mode' => 'internal',
        ])->assertForbidden();
    }

    public function test_invoice_read_only_user_can_view_but_cannot_modify_invoices(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        ]);

        $client = Client::query()->create([
            'name' => 'Invoice Client',
            'company' => 'KitCRM',
            'company_email' => 'invoice-client@example.com',
            'address' => 'Jl. Sudirman 10',
            'country' => 'Indonesia',
            'contact' => '+62 812 0000 0000',
        ]);

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'issued_date' => now()->startOfDay(),
            'due_date' => now()->addWeek()->startOfDay(),
            'service' => 'Implementation',
            'total' => 100000,
            'invoice_status' => 'Draft',
            'balance' => 100000,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/apps/invoice')->assertOk();
        $this->getJson('/api/apps/invoice/clients')->assertOk();
        $this->getJson("/api/apps/invoice/{$invoice->id}")->assertOk();

        $this->postJson('/api/apps/invoice', [
            'client_id' => $client->id,
            'issued_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'service' => 'Forbidden Create',
            'total' => 90000,
        ])->assertForbidden();

        $this->putJson("/api/apps/invoice/{$invoice->id}", [
            'service' => 'Forbidden Update',
        ])->assertForbidden();

        $this->deleteJson("/api/apps/invoice/{$invoice->id}")->assertForbidden();
    }

    public function test_user_without_invoice_access_cannot_open_invoice_api(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => null,
            ],
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/apps/invoice')->assertForbidden();
        $this->getJson('/api/apps/invoice/clients')->assertForbidden();
    }

    public function test_finance_admin_can_manage_invoices_but_cannot_access_admin_or_crm_write_apis(): void
    {
        $user = User::factory()->create([
            'role' => 'maintainer',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'manage',
            ],
        ]);

        $client = Client::query()->create([
            'name' => 'Finance Client',
            'company' => 'KitCRM',
            'company_email' => 'finance-client@example.com',
            'address' => 'Jl. Asia Afrika 20',
            'country' => 'Indonesia',
            'contact' => '+62 812 1111 1111',
        ]);

        $invoice = Invoice::query()->create([
            'client_id' => $client->id,
            'issued_date' => now()->startOfDay(),
            'due_date' => now()->addWeek()->startOfDay(),
            'service' => 'Finance Review',
            'total' => 200000,
            'invoice_status' => 'Draft',
            'balance' => 200000,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/apps/invoice')->assertOk();
        $this->getJson("/api/apps/invoice/{$invoice->id}")->assertOk();

        $this->postJson('/api/apps/invoice', [
            'client_id' => $client->id,
            'issued_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'service' => 'Finance Create',
            'total' => 150000,
        ])->assertCreated();

        $this->putJson("/api/apps/invoice/{$invoice->id}", [
            'service' => 'Finance Updated',
            'invoice_status' => 'Sent',
        ])->assertOk();

        $this->deleteJson("/api/apps/invoice/{$invoice->id}")->assertNoContent();

        $this->getJson('/api/apps/users')->assertForbidden();
        $this->postJson('/api/crm/pelanggan', [
            'nama' => 'Forbidden Finance Customer',
            'email' => 'finance-forbidden@example.com',
        ])->assertForbidden();
        $this->postJson('/api/crm/tiket', [
            'pelangganId' => 999,
            'kategori' => 'general',
            'prioritas' => 'sedang',
            'isiPesan' => 'Finance should not create tickets',
        ])->assertForbidden();
    }
}