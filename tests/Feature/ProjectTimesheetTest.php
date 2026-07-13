<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\ProjectTimesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTimesheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_timesheet_index_displays_summary_and_empty_state(): void
    {
        $this->get(route('admin.projects.timesheets.index'))
            ->assertOk()
            ->assertSee('Timesheets')
            ->assertSee('Add Timesheet')
            ->assertSee('Today Hours')
            ->assertSee('This Week')
            ->assertSee('Billable Hours')
            ->assertSee('Pending Approval')
            ->assertSee('Belum ada Timesheet');
    }

    public function test_timesheet_crud_calculates_duration_from_time_range(): void
    {
        [$project, $milestone, $task, $employee] = $this->timesheetContext();

        $response = $this->post(route('admin.projects.timesheets.store'), [
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'work_date' => '2026-07-13',
            'start_time' => '09:00',
            'end_time' => '11:30',
            'billable' => '1',
            'description' => 'Build delivery report.',
            'status' => ProjectTimesheet::STATUS_SUBMITTED,
        ]);

        $timesheet = ProjectTimesheet::query()->where('description', 'Build delivery report.')->firstOrFail();
        $response->assertRedirect(route('admin.projects.timesheets.show', $timesheet));

        $this->assertSame(150, $timesheet->duration_minutes);
        $this->assertDatabaseHas('project_timesheets', [
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'status' => ProjectTimesheet::STATUS_SUBMITTED,
        ]);

        $this->put(route('admin.projects.timesheets.update', $timesheet), [
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'work_date' => '2026-07-13',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'description' => 'Updated work log.',
            'status' => ProjectTimesheet::STATUS_DRAFT,
        ])->assertRedirect(route('admin.projects.timesheets.show', $timesheet));

        $this->assertSame(120, $timesheet->fresh()->duration_minutes);

        $this->delete(route('admin.projects.timesheets.destroy', $timesheet))
            ->assertRedirect(route('admin.projects.timesheets.index'));

        $this->assertSoftDeleted('project_timesheets', ['id' => $timesheet->id]);
    }

    public function test_timesheet_index_displays_card_data_filters_and_actions(): void
    {
        [$project, $milestone, $task, $employee] = $this->timesheetContext();

        ProjectTimesheet::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'work_date' => '2026-07-13',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'duration_minutes' => 240,
            'billable' => true,
            'status' => ProjectTimesheet::STATUS_APPROVED,
            'description' => 'Implementation sprint.',
        ]);

        $this->get(route('admin.projects.timesheets.index', ['project_id' => $project->id, 'status' => ProjectTimesheet::STATUS_APPROVED]))
            ->assertOk()
            ->assertSee($employee->name)
            ->assertSee($project->title)
            ->assertSee($milestone->title)
            ->assertSee($task->title)
            ->assertSee('4h')
            ->assertSee('Billable')
            ->assertSee('Approved')
            ->assertSee('View')
            ->assertSee('Edit');
    }

    public function test_timesheet_approval_sets_status_approver_and_note(): void
    {
        [$project, $milestone, $task, $employee] = $this->timesheetContext();
        $timesheet = ProjectTimesheet::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'status' => ProjectTimesheet::STATUS_SUBMITTED,
        ]);

        $this->put(route('admin.projects.timesheets.approve', $timesheet), [
            'approval_note' => 'Looks good.',
        ])->assertRedirect(route('admin.projects.timesheets.show', $timesheet));

        $timesheet->refresh();
        $this->assertSame(ProjectTimesheet::STATUS_APPROVED, $timesheet->status);
        $this->assertSame('Looks good.', $timesheet->approval_note);
        $this->assertNotNull($timesheet->approved_by);
        $this->assertNotNull($timesheet->approved_at);

        $this->put(route('admin.projects.timesheets.reject', $timesheet), [
            'approval_note' => 'Please adjust.',
        ])->assertRedirect(route('admin.projects.timesheets.show', $timesheet));

        $this->assertSame(ProjectTimesheet::STATUS_REJECTED, $timesheet->fresh()->status);
    }

    public function test_timesheet_routes_require_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.projects.timesheets.index'))
            ->assertForbidden();
    }

    public function test_timesheet_exports_are_available(): void
    {
        [$project, $milestone, $task, $employee] = $this->timesheetContext();
        ProjectTimesheet::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'description' => 'Exportable log.',
        ]);

        $this->get(route('admin.projects.timesheets.export.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->get(route('admin.projects.timesheets.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_timesheet_relationships_and_project_detail_summary_render(): void
    {
        [$project, $milestone, $task, $employee] = $this->timesheetContext();
        ProjectTimesheet::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'work_date' => '2026-07-13',
            'duration_minutes' => 180,
            'billable' => true,
        ]);

        $this->assertSame(1, $project->timesheets()->count());
        $this->assertSame(1, $task->timesheets()->count());
        $this->assertSame(1, $milestone->timesheets()->count());

        $this->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('Timesheet Summary')
            ->assertSee('3.0h');

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']))
            ->assertOk()
            ->assertSee('Logged Hours: 3.0h');
    }

    public function test_timesheet_dashboard_summary_uses_existing_data(): void
    {
        [$project, $milestone, $task, $employee] = $this->timesheetContext();
        ProjectTimesheet::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'duration_minutes' => 90,
            'billable' => true,
            'status' => ProjectTimesheet::STATUS_SUBMITTED,
        ]);

        $this->get(route('admin.projects.timesheets.index'))
            ->assertOk()
            ->assertSee('1.5h')
            ->assertSee('Pending Approval');
    }

    private function timesheetContext(): array
    {
        $project = Project::factory()->create([
            'project_number' => 'PRJ-2026-TS01',
            'title' => 'Timesheet Delivery',
        ]);
        $milestone = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Build Phase',
        ]);
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Implement module',
        ]);
        $employee = User::factory()->create([
            'name' => 'Nadia Engineer',
        ]);

        return [$project, $milestone, $task, $employee];
    }
}
