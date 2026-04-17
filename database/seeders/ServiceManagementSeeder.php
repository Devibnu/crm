<?php

namespace Database\Seeders;

use App\Models\Pelanggan;
use App\Models\SLA;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceManagementSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $dummyUser = $this->ensureDummyAssignee();

            $sla24Hours = $this->seedSlaDefinition([
                'name' => 'SLA 24 Jam',
                'description' => 'Default SLA lokal untuk target respons awal 24 jam. Pada schema aktif, nilai ini dipetakan ke first_response_minutes = 1440.',
                'category' => null,
                'priority' => 'medium',
                'first_response_minutes' => $this->hoursToMinutes(24),
                'resolution_minutes' => $this->hoursToMinutes(48),
                'warning_before_minutes' => $this->hoursToMinutes(4),
                'auto_escalate' => false,
                'escalation_priority' => null,
                'is_active' => true,
            ]);

            $sla48Hours = $this->seedSlaDefinition([
                'name' => 'SLA 48 Jam',
                'description' => 'Default SLA lokal untuk target penyelesaian 48 jam. Pada schema aktif, nilai ini dipetakan ke resolution_minutes = 2880.',
                'category' => null,
                'priority' => 'high',
                'first_response_minutes' => $this->hoursToMinutes(24),
                'resolution_minutes' => $this->hoursToMinutes(48),
                'warning_before_minutes' => $this->hoursToMinutes(6),
                'auto_escalate' => true,
                'escalation_priority' => 'critical',
                'is_active' => true,
            ]);

            $generalCustomer = $this->seedCustomer([
                'nama' => 'PT Umum Sentosa',
                'email' => 'umum@seed.local',
                'no_hp' => '081200000001',
                'notes' => 'Customer dummy untuk QA ticket kategori umum.',
                'created_by' => $dummyUser->id,
                'updated_by' => $dummyUser->id,
            ]);

            $technicalCustomer = $this->seedCustomer([
                'nama' => 'CV Teknis Nusantara',
                'email' => 'teknis@seed.local',
                'no_hp' => '081200000002',
                'notes' => 'Customer dummy untuk QA ticket kategori teknis.',
                'created_by' => $dummyUser->id,
                'updated_by' => $dummyUser->id,
            ]);

            $billingCustomer = $this->seedCustomer([
                'nama' => 'UD Penagihan Prima',
                'email' => 'penagihan@seed.local',
                'no_hp' => '081200000003',
                'notes' => 'Customer dummy untuk QA ticket kategori penagihan.',
                'created_by' => $dummyUser->id,
                'updated_by' => $dummyUser->id,
            ]);

            $this->seedTicket([
                'code' => 'TCK-SEED-001',
                'customer_id' => $generalCustomer->id,
                'assigned_user_id' => $dummyUser->id,
                'sla_definition_id' => $sla24Hours->id,
                'created_by' => $dummyUser->id,
                'updated_by' => $dummyUser->id,
                'subject' => 'Ticket dummy kategori umum',
                'description' => 'Seed ticket untuk QA lokal. Label PRD: kategori umum, status baru.',
                'category' => 'general',
                'status' => 'open',
                'priority' => 'medium',
                'escalation_level' => 0,
                'alert_state' => 'on_track',
                'first_response_due_at' => now()->addHours(24),
                'resolution_due_at' => now()->addHours(48),
                'last_activity_at' => now(),
                'metadata' => [
                    'seedLabel' => 'service-management',
                    'requestedCategoryLabel' => 'umum',
                    'requestedStatusLabel' => 'baru',
                ],
            ], $dummyUser, 'Ticket created', 'Ticket dummy kategori umum berhasil dibuat untuk environment lokal.');

            $this->seedTicket([
                'code' => 'TCK-SEED-002',
                'customer_id' => $technicalCustomer->id,
                'assigned_user_id' => $dummyUser->id,
                'sla_definition_id' => $sla48Hours->id,
                'created_by' => $dummyUser->id,
                'updated_by' => $dummyUser->id,
                'subject' => 'Ticket dummy kategori teknis',
                'description' => 'Seed ticket untuk QA lokal. Label PRD: kategori teknis, status diproses.',
                'category' => 'technical',
                'status' => 'in_progress',
                'priority' => 'high',
                'escalation_level' => 0,
                'alert_state' => 'on_track',
                'first_response_due_at' => now()->addHours(20),
                'resolution_due_at' => now()->addHours(44),
                'first_responded_at' => now()->subHour(),
                'last_activity_at' => now(),
                'metadata' => [
                    'seedLabel' => 'service-management',
                    'requestedCategoryLabel' => 'teknis',
                    'requestedStatusLabel' => 'diproses',
                ],
            ], $dummyUser, 'Ticket created', 'Ticket dummy kategori teknis berhasil dibuat untuk environment lokal.');

            $this->seedTicket([
                'code' => 'TCK-SEED-003',
                'customer_id' => $billingCustomer->id,
                'assigned_user_id' => $dummyUser->id,
                'sla_definition_id' => $sla24Hours->id,
                'created_by' => $dummyUser->id,
                'updated_by' => $dummyUser->id,
                'subject' => 'Ticket dummy kategori penagihan',
                'description' => 'Seed ticket untuk QA lokal. Label PRD: kategori penagihan, status baru.',
                'category' => 'billing',
                'status' => 'open',
                'priority' => 'medium',
                'escalation_level' => 0,
                'alert_state' => 'on_track',
                'first_response_due_at' => now()->addHours(24),
                'resolution_due_at' => now()->addHours(48),
                'last_activity_at' => now(),
                'metadata' => [
                    'seedLabel' => 'service-management',
                    'requestedCategoryLabel' => 'penagihan',
                    'requestedStatusLabel' => 'baru',
                ],
            ], $dummyUser, 'Ticket created', 'Ticket dummy kategori penagihan berhasil dibuat untuk environment lokal.');
        });
    }

    private function ensureDummyAssignee(): User
    {
        $user = User::query()->find(1);

        if ($user) {
            return $user;
        }

        $user = new User();
        $user->forceFill([
            'id' => 1,
            'full_name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'admin@demo.com',
            'password' => 'admin',
            'avatar' => '/images/avatars/avatar-1.png',
            'role' => 'admin',
            'module_permissions' => User::defaultModulePermissionsForRole('admin'),
            'company' => 'Pixinvent',
            'country' => 'USA',
            'contact' => '(123) 456-7890',
            'current_plan' => 'enterprise',
            'status' => 'active',
            'billing' => 'Auto Debit',
            'task_done' => 1230,
            'project_done' => 568,
            'tax_id' => 'Tax-8894',
            'language' => 'English',
        ]);
        $user->save();

        return $user;
    }

    private function seedSlaDefinition(array $attributes): SLA
    {
        return SLA::query()->updateOrCreate(
            ['name' => $attributes['name']],
            $attributes,
        );
    }

    private function seedCustomer(array $attributes): Pelanggan
    {
        return Pelanggan::query()->updateOrCreate(
            ['email' => $attributes['email']],
            $attributes,
        );
    }

    private function seedTicket(array $attributes, User $actor, string $title, string $description): Ticket
    {
        $ticket = Ticket::query()->updateOrCreate(
            ['code' => $attributes['code']],
            $attributes,
        );

        TicketActivity::query()->updateOrCreate(
            [
                'ticket_id' => $ticket->id,
                'activity_type' => 'ticket_created',
            ],
            [
                'user_id' => $actor->id,
                'title' => $title,
                'description' => $description,
                'metadata' => [
                    'seeded' => true,
                    'source' => 'ServiceManagementSeeder',
                ],
            ],
        );

        if (! $ticket->last_activity_at) {
            $ticket->forceFill([
                'last_activity_at' => now(),
            ])->save();
        }

        return $ticket;
    }

    private function hoursToMinutes(int $hours): int
    {
        return $hours * 60;
    }
}