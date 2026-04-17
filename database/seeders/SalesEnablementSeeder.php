<?php

namespace Database\Seeders;

use App\Models\Forecast;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesEnablementSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $salesUser = $this->ensureSalesAssignee();
            $now = now();

            $leads = [
                'PT Nusantara Jaya' => $this->seedLead([
                    'code' => 'LED-SEED-001',
                    'full_name' => 'Arif Pratama',
                    'email' => 'arif@nusantarajaya.test',
                    'phone' => '081200010001',
                    'company' => 'PT Nusantara Jaya',
                    'source' => 'manual',
                    'status' => 'new',
                    'assigned_user_id' => $salesUser->id,
                    'captured_by' => $salesUser->id,
                    'qualification_notes' => 'Lead baru dari canvassing lokal. Perlu follow-up kebutuhan procurement.',
                    'last_contacted_at' => $now->copy()->subDay(),
                    'qualified_at' => null,
                    'disqualified_at' => null,
                    'metadata' => [
                        'seeded' => true,
                        'requested_name' => 'PT Nusantara Jaya',
                        'requested_assigned_to' => $salesUser->id,
                    ],
                ]),
                'CV Teknologi Mandiri' => $this->seedLead([
                    'code' => 'LED-SEED-002',
                    'full_name' => 'Dewi Lestari',
                    'email' => 'dewi@teknologimandiri.test',
                    'phone' => '081200010002',
                    'company' => 'CV Teknologi Mandiri',
                    'source' => 'campaign',
                    'status' => 'qualified',
                    'assigned_user_id' => $salesUser->id,
                    'captured_by' => $salesUser->id,
                    'qualification_notes' => 'Sudah ada kebutuhan lisensi dan anggaran, siap masuk opportunity.',
                    'last_contacted_at' => $now->copy()->subHours(6),
                    'qualified_at' => $now->copy()->subHours(4),
                    'disqualified_at' => null,
                    'metadata' => [
                        'seeded' => true,
                        'requested_name' => 'CV Teknologi Mandiri',
                        'requested_assigned_to' => $salesUser->id,
                    ],
                ]),
                'UD Sumber Rejeki' => $this->seedLead([
                    'code' => 'LED-SEED-003',
                    'full_name' => 'Budi Santoso',
                    'email' => 'budi@sumberrejeki.test',
                    'phone' => '081200010003',
                    'company' => 'UD Sumber Rejeki',
                    'source' => 'form',
                    'status' => 'disqualified',
                    'assigned_user_id' => $salesUser->id,
                    'captured_by' => $salesUser->id,
                    'qualification_notes' => 'Prospek tidak lanjut karena anggaran dialihkan ke vendor existing.',
                    'last_contacted_at' => $now->copy()->subDays(2),
                    'qualified_at' => null,
                    'disqualified_at' => $now->copy()->subDay(),
                    'metadata' => [
                        'seeded' => true,
                        'requested_name' => 'UD Sumber Rejeki',
                        'requested_assigned_to' => $salesUser->id,
                    ],
                ]),
            ];

            $opportunities = [
                'Deal Laptop Enterprise' => $this->seedOpportunity([
                    'code' => 'OPP-SEED-001',
                    'lead_id' => $leads['PT Nusantara Jaya']->id,
                    'assigned_user_id' => $salesUser->id,
                    'name' => 'Deal Laptop Enterprise',
                    'stage' => 'prospecting',
                    'amount' => 185000000,
                    'currency' => 'IDR',
                    'probability' => 25,
                    'expected_close_date' => $now->copy()->addDays(21)->toDateString(),
                    'status_notes' => 'Masih tahap discovery kebutuhan device untuk 75 pengguna.',
                    'closed_at' => null,
                    'metadata' => [
                        'seeded' => true,
                        'requested_lead_company' => 'PT Nusantara Jaya',
                    ],
                ]),
                'Software Subscription' => $this->seedOpportunity([
                    'code' => 'OPP-SEED-002',
                    'lead_id' => $leads['CV Teknologi Mandiri']->id,
                    'assigned_user_id' => $salesUser->id,
                    'name' => 'Software Subscription',
                    'stage' => 'negotiation',
                    'amount' => 96000000,
                    'currency' => 'IDR',
                    'probability' => 70,
                    'expected_close_date' => $now->copy()->addDays(10)->toDateString(),
                    'status_notes' => 'Negosiasi diskon tahunan dan jumlah seat enterprise.',
                    'closed_at' => null,
                    'metadata' => [
                        'seeded' => true,
                        'requested_lead_company' => 'CV Teknologi Mandiri',
                    ],
                ]),
                'Cloud Hosting Contract' => $this->seedOpportunity([
                    'code' => 'OPP-SEED-003',
                    'lead_id' => $leads['CV Teknologi Mandiri']->id,
                    'assigned_user_id' => $salesUser->id,
                    'name' => 'Cloud Hosting Contract',
                    'stage' => 'closed_won',
                    'amount' => 150000000,
                    'currency' => 'IDR',
                    'probability' => 100,
                    'expected_close_date' => $now->copy()->subDays(2)->toDateString(),
                    'status_notes' => 'Kontrak ditandatangani dan siap handover ke delivery.',
                    'closed_at' => $now->copy()->subDays(2),
                    'metadata' => [
                        'seeded' => true,
                        'requested_lead_company' => 'CV Teknologi Mandiri',
                    ],
                ]),
            ];

            $this->seedQuotation([
                'quote_number' => 'QTN-SEED-001',
                'opportunity_id' => $opportunities['Software Subscription']->id,
                'title' => 'Quotation Software Subscription',
                'amount' => 96000000,
                'currency' => 'IDR',
                'valid_until' => $now->copy()->addDays(14)->toDateString(),
                'status' => 'draft',
                'approval_notes' => 'Menunggu approval pricing final dari sales manager.',
                'submitted_at' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'metadata' => [
                    'seeded' => true,
                    'requested_opportunity_name' => 'Software Subscription',
                ],
            ]);

            $this->seedQuotation([
                'quote_number' => 'QTN-SEED-002',
                'opportunity_id' => $opportunities['Cloud Hosting Contract']->id,
                'title' => 'Quotation Cloud Hosting Contract',
                'amount' => 150000000,
                'currency' => 'IDR',
                'valid_until' => $now->copy()->addDays(30)->toDateString(),
                'status' => 'approved',
                'approval_notes' => 'Quotation disetujui dan menjadi dasar kontrak aktif.',
                'submitted_at' => $now->copy()->subDays(5),
                'approved_at' => $now->copy()->subDays(3),
                'rejected_at' => null,
                'metadata' => [
                    'seeded' => true,
                    'requested_opportunity_name' => 'Cloud Hosting Contract',
                ],
            ]);

            $this->seedForecast($opportunities);
        });
    }

    private function ensureSalesAssignee(): User
    {
        $salesUser = User::query()->where('email', 'sales@demo.com')->first();

        if ($salesUser) {
            return $salesUser;
        }

        $salesUser = User::query()->where('role', 'sales')->first();

        if ($salesUser) {
            return $salesUser;
        }

        $salesUser = new User();
        $salesUser->forceFill([
            'full_name' => 'Seno Sales',
            'username' => 'senosales',
            'email' => 'sales@demo.com',
            'password' => 'sales',
            'avatar' => '/images/avatars/avatar-7.png',
            'role' => 'sales',
            'module_permissions' => User::defaultModulePermissionsForRole('sales'),
            'company' => 'Pixinvent',
            'country' => 'Indonesia',
            'contact' => '081200000007',
            'current_plan' => 'enterprise',
            'status' => 'active',
            'billing' => 'Auto Debit',
            'task_done' => 120,
            'project_done' => 18,
            'tax_id' => 'Tax-7707',
            'language' => 'Indonesian',
        ]);
        $salesUser->save();

        return $salesUser;
    }

    private function seedLead(array $attributes): Lead
    {
        return Lead::query()->updateOrCreate(
            ['code' => $attributes['code']],
            $attributes,
        );
    }

    private function seedOpportunity(array $attributes): Opportunity
    {
        return Opportunity::query()->updateOrCreate(
            ['code' => $attributes['code']],
            $attributes,
        );
    }

    private function seedQuotation(array $attributes): Quotation
    {
        return Quotation::query()->updateOrCreate(
            ['quote_number' => $attributes['quote_number']],
            $attributes,
        );
    }

    /**
     * @param  array<string, Opportunity>  $opportunities
     */
    private function seedForecast(array $opportunities): Forecast
    {
        $currentMonth = Carbon::now();
        $closedWonAmount = collect($opportunities)
            ->filter(fn (Opportunity $opportunity): bool => $opportunity->stage === 'closed_won')
            ->sum(fn (Opportunity $opportunity): float => (float) $opportunity->amount);

        $weightedAmount = collect($opportunities)
            ->sum(fn (Opportunity $opportunity): float => ((float) $opportunity->amount) * ($opportunity->probability / 100));

        return Forecast::query()->updateOrCreate(
            [
                'period_label' => $currentMonth->translatedFormat('F Y'),
                'snapshot_date' => $currentMonth->copy()->startOfMonth()->toDateString(),
            ],
            [
                'forecast_amount' => $closedWonAmount,
                'weighted_amount' => $weightedAmount,
                'committed_amount' => $closedWonAmount,
                'status' => 'published',
                'notes' => 'Forecast seed bulan berjalan dihitung dari opportunity closed won sesuai kebutuhan QA lokal.',
                'metadata' => [
                    'seeded' => true,
                    'requested_month' => $currentMonth->format('Y-m'),
                    'requested_revenue_total' => $closedWonAmount,
                ],
            ],
        );
    }
}