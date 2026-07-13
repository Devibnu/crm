<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\ProjectTimesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_displays_enterprise_reporting_dashboard(): void
    {
        [$project, $manager, $customer] = $this->reportContext();

        $this->get(route('admin.projects.reports.index', [
            'project_id' => $project->id,
            'project_manager_id' => $manager->id,
            'customer_id' => $customer->id,
            'department' => 'developer',
            'status' => 'active',
            'priority' => 'high',
        ]))
            ->assertOk()
            ->assertSee('Project Reports')
            ->assertSee('Portfolio analytics, delivery performance, workload, milestones, budget, and productivity insights.')
            ->assertSee('Generate Report')
            ->assertSee('Export')
            ->assertSee('Total Projects')
            ->assertSee('Active Projects')
            ->assertSee('Overall Completion %')
            ->assertSee('Billable Hours')
            ->assertSee('Budget Utilization %')
            ->assertSee('Total Team Members')
            ->assertSee('Project Status Distribution')
            ->assertSee('Project Completion Trend')
            ->assertSee('Workload By Employee')
            ->assertSee('Milestone Health')
            ->assertSee('Timesheet Summary')
            ->assertSee('Recent Delivery')
            ->assertSee('Top Delayed Projects')
            ->assertSee('Enterprise Reporting Project')
            ->assertSee('Delivery Milestone')
            ->assertSee('In Progress');
    }

    public function test_reports_empty_state_is_displayed_without_project_data(): void
    {
        $this->get(route('admin.projects.reports.index'))
            ->assertOk()
            ->assertSee('No report data available.')
            ->assertSee('Create Project');
    }

    public function test_report_permission_is_enforced(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.projects.reports.index'))
            ->assertForbidden();
    }

    public function test_report_exports_are_available(): void
    {
        $this->reportContext();

        $this->get(route('admin.projects.reports.export', ['type' => 'csv']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('Project');

        $this->get(route('admin.projects.reports.export', ['type' => 'excel']))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="project-reports.xls"');

        $this->get(route('admin.projects.reports.export', ['type' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('admin.projects.reports.export', ['type' => 'print']))
            ->assertOk()
            ->assertSee('Generated');
    }

    /** @return array{0: Project, 1: User, 2: Customer} */
    private function reportContext(): array
    {
        $manager = User::factory()->create(['name' => 'Report Manager']);
        $employee = User::factory()->create(['name' => 'Report Engineer']);
        $customer = Customer::factory()->create(['name' => 'Executive Customer']);
        $project = Project::factory()->create([
            'customer_id' => $customer->id,
            'project_manager_id' => $manager->id,
            'project_number' => 'PRJ-REPORT-001',
            'title' => 'Enterprise Reporting Project',
            'status' => 'active',
            'budget' => 100000000,
            'progress' => 65,
            'start_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(20)->toDateString(),
        ]);
        $milestone = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Delivery Milestone',
            'status' => 'completed',
            'due_date' => now()->addDays(8)->toDateString(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'assignee_id' => $employee->id,
            'title' => 'High Priority Report Task',
            'priority' => 'high',
            'status' => 'in_progress',
        ]);
        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
            'role' => 'developer',
        ]);
        ProjectTimesheet::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'user_id' => $employee->id,
            'duration_minutes' => 180,
            'billable' => true,
            'status' => ProjectTimesheet::STATUS_SUBMITTED,
        ]);

        Project::factory()->create([
            'title' => 'Delayed Report Project',
            'status' => 'delayed',
            'due_date' => now()->subDays(4)->toDateString(),
            'progress' => 25,
        ]);

        return [$project, $manager, $customer];
    }
}
