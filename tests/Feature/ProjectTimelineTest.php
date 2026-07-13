<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_can_be_opened(): void
    {
        $project = Project::factory()->create([
            'title' => 'Timeline Enterprise Project',
            'status' => 'active',
            'start_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addDays(20)->toDateString(),
            'progress' => 40,
        ]);
        ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Discovery Milestone',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->get(route('admin.projects.timeline.index'))
            ->assertOk()
            ->assertSee('Project Timeline')
            ->assertSee('Timeline Enterprise Project')
            ->assertSee('Discovery Milestone')
            ->assertSee('TODAY');
    }

    public function test_timeline_permission_is_enforced(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.projects.timeline.index'))
            ->assertForbidden();
    }

    public function test_timeline_filters_by_project_owner_status_and_date_range(): void
    {
        $owner = User::factory()->create(['name' => 'Timeline Owner']);
        $matchingProject = Project::factory()->create([
            'project_manager_id' => $owner->id,
            'title' => 'Filtered Timeline Project',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $matchingProject->id,
            'assignee_id' => $owner->id,
            'title' => 'Filtered Timeline Task',
            'status' => 'in_progress',
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);
        $hiddenProject = Project::factory()->create([
            'title' => 'Hidden Timeline Project',
            'status' => 'completed',
            'start_date' => now()->subMonth()->toDateString(),
            'due_date' => now()->subDays(20)->toDateString(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $hiddenProject->id,
            'title' => 'Hidden Timeline Task',
            'status' => 'done',
        ]);

        $this->get(route('admin.projects.timeline.index', [
            'q' => 'Filtered',
            'project_id' => $matchingProject->id,
            'owner_id' => $owner->id,
            'status' => 'in_progress',
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->addDays(14)->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('Filtered Timeline Project')
            ->assertSee('Filtered Timeline Task')
            ->assertDontSee('Hidden Timeline Task');
    }

    public function test_timeline_pagination_renders(): void
    {
        Project::factory()->count(9)->create();

        $this->get(route('admin.projects.timeline.index'))
            ->assertOk()
            ->assertSee('pagination', false);
    }

    public function test_timeline_shows_upcoming_overdue_and_completed_states(): void
    {
        $project = Project::factory()->create([
            'title' => 'Signal Timeline Project',
            'status' => 'active',
        ]);
        $upcomingMilestone = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Upcoming Signal Milestone',
            'status' => 'in_progress',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $upcomingMilestone->id,
            'title' => 'Overdue Signal Task',
            'status' => 'todo',
            'due_date' => now()->subDays(2)->toDateString(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Completed Signal Task',
            'status' => 'done',
            'completed_at' => now(),
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $this->get(route('admin.projects.timeline.index'))
            ->assertOk()
            ->assertSee('Due in 3 days')
            ->assertSee('Overdue by 2 days')
            ->assertSee('Completed');
    }

    public function test_timeline_responsive_view_mode_controls_render(): void
    {
        Project::factory()->create(['title' => 'Responsive Timeline Project']);

        $this->get(route('admin.projects.timeline.index'))
            ->assertOk()
            ->assertSee('data-project-timeline', false)
            ->assertSee('data-view-mode="month"', false)
            ->assertSee('Today')
            ->assertSee('Week')
            ->assertSee('Month')
            ->assertSee('Quarter');
    }

    public function test_timeline_empty_state_renders_without_projects(): void
    {
        $this->get(route('admin.projects.timeline.index'))
            ->assertOk()
            ->assertSee('No timeline available')
            ->assertSee('Create your first project to start planning.')
            ->assertSee('Add Project');
    }
}
