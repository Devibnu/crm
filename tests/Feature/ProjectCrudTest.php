<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\ProjectTaskAttachment;
use App\Models\ProjectTaskChecklist;
use App\Models\ProjectTaskComment;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project_from_accepted_quotation_prefills_source_fields(): void
    {
        [$customer, $lead, $opportunity, $quotation] = $this->wonDealSource();

        $this->get(route('admin.projects.create', ['quotation_id' => $quotation->id]))
            ->assertOk()
            ->assertSee('Create Project')
            ->assertSee('<select name="customer_id">', false)
            ->assertSee('value="'.$customer->id.'" selected', false)
            ->assertSee($customer->name)
            ->assertSee('<select name="lead_id">', false)
            ->assertSee('value="'.$lead->id.'" selected', false)
            ->assertSee($lead->name)
            ->assertSee('<select name="opportunity_id">', false)
            ->assertSee('value="'.$opportunity->id.'" selected', false)
            ->assertSee($opportunity->title)
            ->assertSee('<select name="quotation_id">', false)
            ->assertSee('value="'.$quotation->id.'" selected', false)
            ->assertSee($quotation->quote_number.' - '.$quotation->title)
            ->assertSee('value="'.$quotation->title.'"', false)
            ->assertSee('value="88000000.00"', false)
            ->assertSee('Created from Deal Won.')
            ->assertSee('Quotation: '.$quotation->quote_number)
            ->assertSee('Opportunity: '.$opportunity->title)
            ->assertSee('Customer: '.$customer->name);
    }

    public function test_create_project_page_displays_existing_customer(): void
    {
        [$customer] = $this->wonDealSource();

        $this->get(route('admin.projects.create'))
            ->assertOk()
            ->assertSee('Tanpa customer')
            ->assertSee('<select name="customer_id">', false)
            ->assertSee('value="'.$customer->id.'"', false)
            ->assertSee($customer->name);
    }

    public function test_create_project_page_displays_existing_lead(): void
    {
        [, $lead] = $this->wonDealSource();

        $this->get(route('admin.projects.create'))
            ->assertOk()
            ->assertSee('Tanpa lead')
            ->assertSee('<select name="lead_id">', false)
            ->assertSee('value="'.$lead->id.'"', false)
            ->assertSee($lead->name);
    }

    public function test_create_project_page_displays_existing_opportunity(): void
    {
        [, , $opportunity] = $this->wonDealSource();

        $this->get(route('admin.projects.create'))
            ->assertOk()
            ->assertSee('Tanpa opportunity')
            ->assertSee('<select name="opportunity_id">', false)
            ->assertSee('value="'.$opportunity->id.'"', false)
            ->assertSee($opportunity->title);
    }

    public function test_create_project_page_displays_existing_quotation(): void
    {
        [, , , $quotation] = $this->wonDealSource();

        $this->get(route('admin.projects.create'))
            ->assertOk()
            ->assertSee('Tanpa quotation')
            ->assertSee('<select name="quotation_id">', false)
            ->assertSee('value="'.$quotation->id.'"', false)
            ->assertSee($quotation->quote_number.' - '.$quotation->title);
    }

    public function test_store_project_saves_all_source_ids(): void
    {
        [$customer, $lead, $opportunity, $quotation] = $this->wonDealSource();
        $manager = User::factory()->create(['name' => 'Project Manager User']);

        $this->post(route('admin.projects.store'), [
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'title' => 'ERP Rollout Project',
            'description' => 'Created from Deal Won.',
            'status' => 'planning',
            'budget' => 88000000,
            'start_date' => '2026-07-10',
            'due_date' => '2026-08-10',
            'project_manager_id' => $manager->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'title' => 'ERP Rollout Project',
            'status' => 'planning',
            'budget' => '88000000.00',
            'progress' => 0,
            'project_manager_id' => $manager->id,
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'event' => 'project_created',
            'description' => 'Project Created',
        ]);
    }

    public function test_project_cannot_be_duplicated_for_same_quotation(): void
    {
        [$customer, $lead, $opportunity, $quotation] = $this->wonDealSource();
        $project = Project::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'title' => 'Existing Deal Project',
        ]);

        $this->get(route('admin.projects.create', ['quotation_id' => $quotation->id]))
            ->assertRedirect(route('admin.projects.show', $project));

        $this->post(route('admin.projects.store'), [
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'title' => 'Duplicate Deal Project',
            'status' => 'planning',
            'budget' => 88000000,
            'progress' => 0,
        ])->assertRedirect(route('admin.projects.show', $project));

        $this->assertSame(1, Project::query()->where('quotation_id', $quotation->id)->count());
    }

    public function test_omnichannel_shows_open_project_after_project_is_created(): void
    {
        [$customer, $lead, $opportunity, $quotation, $conversation, $ticket] = $this->wonDealSourceWithConversation();
        $project = Project::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'title' => 'Omnichannel Project',
        ]);

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Open Customer')
            ->assertSee('Open Lead')
            ->assertSee('Open Opportunity')
            ->assertSee('Open Quotation')
            ->assertSee('Open Ticket')
            ->assertSee(route('admin.service.tickets.show', $ticket), false)
            ->assertSee('Open Deal')
            ->assertSee('Open Project')
            ->assertSee(route('admin.projects.show', $project), false)
            ->assertDontSee(route('admin.projects.create', ['quotation_id' => $quotation->id]), false);
    }

    public function test_quotation_detail_shows_open_project_after_project_is_created(): void
    {
        [$customer, $lead, $opportunity, $quotation] = $this->wonDealSource();
        $project = Project::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'title' => 'Quotation Detail Project',
        ]);

        $this->get(route('admin.sales.deals.show', $quotation))
            ->assertOk()
            ->assertSee('Open Project')
            ->assertSee(route('admin.projects.show', $project), false)
            ->assertDontSee(route('admin.projects.create', ['quotation_id' => $quotation->id]), false);
    }

    public function test_project_overview_can_be_updated_and_logs_status_change(): void
    {
        $project = Project::factory()->create([
            'status' => 'planning',
            'progress' => 90,
        ]);
        $manager = User::factory()->create(['name' => 'Delivery Lead']);

        $this->put(route('admin.projects.update', $project), [
            'customer_id' => $project->customer_id,
            'lead_id' => $project->lead_id,
            'opportunity_id' => $project->opportunity_id,
            'quotation_id' => $project->quotation_id,
            'title' => 'Updated Foundation Project',
            'description' => 'Updated overview',
            'status' => 'active',
            'budget' => 99000000,
            'start_date' => '2026-07-11',
            'due_date' => '2026-08-11',
            'project_manager_id' => $manager->id,
        ])->assertRedirect(route('admin.projects.show', $project));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Foundation Project',
            'status' => 'active',
            'progress' => 0,
            'project_manager_id' => $manager->id,
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'project_updated',
            'description' => 'Project Updated',
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'status_changed',
        ]);
    }

    public function test_project_members_can_be_added_without_duplicate_user(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['name' => 'Foundation Developer']);

        $this->post(route('admin.projects.members.store', $project), [
            'user_id' => $user->id,
            'role' => 'developer',
        ])->assertRedirect(route('admin.projects.show', $project));

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'developer',
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'member_added',
            'description' => 'Member Added: Foundation Developer',
        ]);

        $this->post(route('admin.projects.members.store', $project), [
            'user_id' => $user->id,
            'role' => 'developer',
        ])->assertSessionHasErrors('user_id');

        $this->assertSame(1, ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->count());
    }

    public function test_milestones_drive_project_progress_calculation(): void
    {
        $project = Project::factory()->create(['progress' => 0]);

        $this->post(route('admin.projects.milestones.store', $project), [
            'title' => 'Requirement',
            'status' => 'completed',
            'due_date' => '2026-07-20',
        ])->assertRedirect(route('admin.projects.show', $project));

        $this->post(route('admin.projects.milestones.store', $project), [
            'title' => 'Design',
            'status' => 'pending',
        ])->assertRedirect(route('admin.projects.show', $project));

        $this->post(route('admin.projects.milestones.store', $project), [
            'title' => 'Development',
            'status' => 'pending',
        ])->assertRedirect(route('admin.projects.show', $project));

        $this->assertSame(33, $project->fresh()->progress);

        $milestone = ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->where('title', 'Design')
            ->firstOrFail();

        $this->put(route('admin.projects.milestones.update', [$project, $milestone]), [
            'title' => $milestone->title,
            'description' => $milestone->description,
            'status' => 'completed',
            'due_date' => null,
        ])->assertRedirect(route('admin.projects.show', $project));

        $this->assertSame(67, $project->fresh()->progress);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'milestone_completed',
            'description' => 'Milestone Completed: Design',
        ]);
    }

    public function test_milestone_index_and_detail_show_enterprise_progress(): void
    {
        $project = Project::factory()->create([
            'project_number' => 'PRJ-ENTERPRISE-001',
            'title' => 'Enterprise Delivery',
        ]);
        $milestone = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'UAT Signoff',
            'status' => 'in_progress',
            'color' => 'green',
            'icon' => 'calendar',
            'start_date' => '2026-07-01',
            'due_date' => '2026-07-20',
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'status' => 'done',
            'completed_at' => now(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'status' => 'todo',
        ]);

        $this->get(route('admin.projects.milestones.index'))
            ->assertOk()
            ->assertSee('Milestones')
            ->assertSee('UAT Signoff')
            ->assertSee('Enterprise Delivery')
            ->assertSee('50%');

        $this->get(route('admin.projects.milestones.show', [$project, $milestone]))
            ->assertOk()
            ->assertSee('UAT Signoff')
            ->assertSee('1 / 2')
            ->assertSee('50%');
    }

    public function test_project_dashboard_shows_milestone_summary_counter(): void
    {
        ProjectMilestone::factory()->create(['status' => 'planning']);
        ProjectMilestone::factory()->create(['status' => 'completed']);
        ProjectMilestone::factory()->create(['status' => 'delayed']);

        $this->get(route('admin.projects.dashboard'))
            ->assertOk()
            ->assertSee('Milestone Health')
            ->assertSee('Total')
            ->assertSee('Open')
            ->assertSee('Completed')
            ->assertSee('Delayed');
    }

    public function test_milestone_routes_require_milestone_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.projects.milestones.index'))
            ->assertForbidden();
    }

    public function test_milestone_crud_accepts_enterprise_fields(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('admin.projects.milestones.store', $project), [
            'title' => 'Go Live',
            'description' => 'Production release checkpoint.',
            'status' => 'planning',
            'color' => 'violet',
            'icon' => 'deal',
            'start_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'redirect_to' => 'milestone',
        ]);

        $milestone = ProjectMilestone::query()->where('title', 'Go Live')->firstOrFail();
        $response->assertRedirect(route('admin.projects.milestones.show', [$project, $milestone]));

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'title' => 'Go Live',
            'status' => 'planning',
            'color' => 'violet',
            'icon' => 'deal',
        ]);

        $this->put(route('admin.projects.milestones.update', [$project, $milestone]), [
            'title' => 'Go Live Revised',
            'description' => 'Updated checkpoint.',
            'status' => 'in_progress',
            'color' => 'amber',
            'icon' => 'activity',
            'start_date' => '2026-08-01',
            'due_date' => '2026-08-12',
            'redirect_to' => 'milestone',
        ])->assertRedirect(route('admin.projects.milestones.show', [$project, $milestone]));

        $this->assertDatabaseHas('project_milestones', [
            'id' => $milestone->id,
            'title' => 'Go Live Revised',
            'status' => 'in_progress',
            'color' => 'amber',
            'icon' => 'activity',
        ]);
    }

    public function test_task_completion_refreshes_linked_milestone_status(): void
    {
        $project = Project::factory()->create(['progress' => 0]);
        $milestone = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'status' => 'planning',
            'completed_at' => null,
        ]);
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'status' => 'review',
            'completed_at' => null,
        ]);

        $this->put(route('admin.projects.tasks.status', [$project, $task]), [
            'status' => 'done',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']));

        $milestone->refresh();
        $this->assertSame('completed', $milestone->status);
        $this->assertNotNull($milestone->completed_at);
    }

    public function test_deleting_milestone_can_move_tasks_to_another_milestone(): void
    {
        $project = Project::factory()->create();
        $source = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Source',
        ]);
        $target = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Target',
        ]);
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $source->id,
            'status' => 'todo',
        ]);

        $this->delete(route('admin.projects.milestones.destroy', [$project, $source]), [
            'task_action' => 'move',
            'target_milestone_id' => $target->id,
        ])->assertRedirect(route('admin.projects.milestones.index'));

        $this->assertSoftDeleted('project_milestones', ['id' => $source->id]);
        $this->assertDatabaseHas('project_tasks', [
            'id' => $task->id,
            'milestone_id' => $target->id,
        ]);
    }

    public function test_deleting_milestone_can_delete_linked_tasks(): void
    {
        $project = Project::factory()->create();
        $milestone = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Deprecated Phase',
        ]);
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
        ]);

        $this->delete(route('admin.projects.milestones.destroy', [$project, $milestone]), [
            'task_action' => 'delete_tasks',
        ])->assertRedirect(route('admin.projects.milestones.index'));

        $this->assertSoftDeleted('project_milestones', ['id' => $milestone->id]);
        $this->assertDatabaseMissing('project_tasks', ['id' => $task->id]);
    }

    public function test_task_can_be_created_from_project_detail(): void
    {
        $project = Project::factory()->create(['progress' => 0]);
        $assignee = User::factory()->create(['name' => 'Task Owner']);
        $milestone = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Implementation',
        ]);

        $this->post(route('admin.projects.tasks.store', $project), [
            'milestone_id' => $milestone->id,
            'assignee_id' => $assignee->id,
            'title' => 'Configure CRM Workspace',
            'description' => 'Prepare delivery task.',
            'priority' => 'high',
            'start_date' => '2026-07-10',
            'due_date' => '2026-07-20',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']));

        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'assignee_id' => $assignee->id,
            'title' => 'Configure CRM Workspace',
            'status' => 'todo',
            'priority' => 'high',
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'task_created',
            'description' => 'Task Created: Configure CRM Workspace',
        ]);
    }

    public function test_task_status_can_change_and_done_sets_completed_at(): void
    {
        $project = Project::factory()->create(['progress' => 0]);
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Review Delivery',
            'status' => 'review',
            'completed_at' => null,
        ]);

        $this->put(route('admin.projects.tasks.status', [$project, $task]), [
            'status' => 'done',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']));

        $task->refresh();
        $this->assertSame('done', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'task_status_changed',
            'description' => 'Task Status Changed: Review Delivery',
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'task_completed',
            'description' => 'Task Completed: Review Delivery',
        ]);
    }

    public function test_project_progress_uses_tasks_when_tasks_exist(): void
    {
        $project = Project::factory()->create(['progress' => 0]);
        ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'status' => 'completed',
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'status' => 'done',
            'completed_at' => now(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
        ]);

        app(\App\Services\Projects\ProjectProgressEngine::class)->refresh($project);

        $this->assertSame(50, $project->fresh()->progress);
    }

    public function test_project_progress_falls_back_to_milestones_without_tasks(): void
    {
        $project = Project::factory()->create(['progress' => 0]);
        ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'status' => 'completed',
        ]);
        ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        app(\App\Services\Projects\ProjectProgressEngine::class)->refresh($project);

        $this->assertSame(50, $project->fresh()->progress);
    }

    public function test_task_from_another_project_cannot_be_updated_through_wrong_project(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $otherProject->id,
            'status' => 'todo',
        ]);

        $this->put(route('admin.projects.tasks.status', [$project, $task]), [
            'status' => 'in_progress',
        ])->assertNotFound();

        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_project_detail_tasks_tab_displays_empty_state_and_task_list(): void
    {
        $emptyProject = Project::factory()->create();

        $this->get(route('admin.projects.show', ['project' => $emptyProject, 'tab' => 'tasks']))
            ->assertOk()
            ->assertSee('Belum ada Task')
            ->assertSee('Mulai pecah pekerjaan project menjadi task delivery.')
            ->assertSee('Add Task');

        $project = Project::factory()->create();
        $assignee = User::factory()->create(['name' => 'Delivery Engineer']);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $assignee->id,
            'title' => 'Build Project Task MVP',
            'status' => 'in_progress',
            'priority' => 'critical',
            'due_date' => '2026-07-20',
        ]);

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']))
            ->assertOk()
            ->assertSee('Total Task')
            ->assertSee('Build Project Task MVP')
            ->assertSee('Delivery Engineer')
            ->assertSee('Critical')
            ->assertSee('In Progress')
            ->assertSee('Move to Review');
    }

    public function test_checklist_can_be_created_for_project_task(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Checklist Parent Task',
        ]);

        $this->post(route('admin.projects.tasks.checklists.store', [$project, $task]), [
            'title' => 'Prepare implementation note',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']));

        $this->assertDatabaseHas('project_task_checklists', [
            'project_task_id' => $task->id,
            'title' => 'Prepare implementation note',
            'is_completed' => false,
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'checklist_created',
            'description' => 'Checklist Created: Prepare implementation note',
        ]);
    }

    public function test_checklist_can_only_be_created_on_task_owned_by_project(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $otherProject->id,
        ]);

        $this->post(route('admin.projects.tasks.checklists.store', [$project, $task]), [
            'title' => 'Invalid checklist',
        ])->assertNotFound();

        $this->assertDatabaseMissing('project_task_checklists', [
            'title' => 'Invalid checklist',
        ]);
    }

    public function test_checklist_can_be_completed_and_reopened_with_completion_metadata(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Checklist Toggle Task',
        ]);
        $checklist = ProjectTaskChecklist::factory()->create([
            'project_task_id' => $task->id,
            'title' => 'Toggle checklist item',
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        $this->put(route('admin.projects.tasks.checklists.toggle', [$project, $task, $checklist]))
            ->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']));

        $checklist->refresh();
        $this->assertTrue($checklist->is_completed);
        $this->assertNotNull($checklist->completed_at);
        $this->assertSame(auth()->id(), $checklist->completed_by);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'checklist_completed',
            'description' => 'Checklist Completed: Toggle checklist item',
        ]);

        $this->put(route('admin.projects.tasks.checklists.toggle', [$project, $task, $checklist]))
            ->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']));

        $checklist->refresh();
        $this->assertFalse($checklist->is_completed);
        $this->assertNull($checklist->completed_at);
        $this->assertNull($checklist->completed_by);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'checklist_reopened',
            'description' => 'Checklist Reopened: Toggle checklist item',
        ]);
    }

    public function test_checklist_toggle_requires_checklist_to_belong_to_task(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
        ]);
        $otherTask = ProjectTask::factory()->create([
            'project_id' => $project->id,
        ]);
        $checklist = ProjectTaskChecklist::factory()->create([
            'project_task_id' => $otherTask->id,
            'is_completed' => false,
        ]);

        $this->put(route('admin.projects.tasks.checklists.toggle', [$project, $task, $checklist]))
            ->assertNotFound();

        $this->assertFalse($checklist->fresh()->is_completed);
    }

    public function test_checklist_progress_appears_on_project_detail_tasks_tab(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Task With Checklist Progress',
        ]);
        ProjectTaskChecklist::factory()->create([
            'project_task_id' => $task->id,
            'title' => 'Completed checklist item',
            'is_completed' => true,
            'completed_at' => now(),
        ]);
        ProjectTaskChecklist::factory()->create([
            'project_task_id' => $task->id,
            'title' => 'Open checklist item',
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']))
            ->assertOk()
            ->assertSee('Task With Checklist Progress')
            ->assertSee('Checklist')
            ->assertSee('1/2 completed')
            ->assertSee('50%')
            ->assertSee('Completed checklist item')
            ->assertSee('Open checklist item')
            ->assertSee('Add checklist item');
    }

    public function test_checklist_progress_appears_on_kanban_card(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Kanban Checklist Task',
            'status' => 'in_progress',
        ]);
        ProjectTaskChecklist::factory()->create([
            'project_task_id' => $task->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);
        ProjectTaskChecklist::factory()->create([
            'project_task_id' => $task->id,
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']))
            ->assertOk()
            ->assertSee('Kanban Checklist Task')
            ->assertSee('Checklist')
            ->assertSee('1/2');
    }

    public function test_task_comment_can_be_created_and_logs_activity(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Comment Parent Task',
        ]);

        $this->post(route('admin.projects.tasks.comments.store', [$project, $task]), [
            'comment' => 'Please confirm delivery scope.',
            'redirect_tab' => 'kanban',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']));

        $this->assertDatabaseHas('project_task_comments', [
            'project_task_id' => $task->id,
            'user_id' => auth()->id(),
            'comment' => 'Please confirm delivery scope.',
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'comment_added',
            'description' => 'Comment Added: Comment Parent Task',
        ]);
    }

    public function test_task_comment_can_be_updated_by_owner_and_logs_activity(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Editable Comment Task',
        ]);
        $comment = ProjectTaskComment::factory()->create([
            'project_task_id' => $task->id,
            'user_id' => auth()->id(),
            'comment' => 'Original comment',
        ]);

        $this->put(route('admin.projects.tasks.comments.update', [$project, $task, $comment]), [
            'comment' => 'Updated comment',
            'redirect_tab' => 'tasks',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']));

        $this->assertSame('Updated comment', $comment->fresh()->comment);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'comment_updated',
            'description' => 'Comment Updated: Editable Comment Task',
        ]);
    }

    public function test_task_comment_can_be_deleted_by_admin_and_logs_activity(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Deletable Comment Task',
        ]);
        $comment = ProjectTaskComment::factory()->create([
            'project_task_id' => $task->id,
            'user_id' => User::factory(),
            'comment' => 'Remove this comment',
        ]);

        $this->delete(route('admin.projects.tasks.comments.destroy', [$project, $task, $comment]), [
            'redirect_tab' => 'kanban',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']));

        $this->assertDatabaseMissing('project_task_comments', [
            'id' => $comment->id,
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'comment_deleted',
            'description' => 'Comment Deleted: Deletable Comment Task',
        ]);
    }

    public function test_task_comment_requires_task_to_belong_to_project(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $otherProject->id,
        ]);

        $this->post(route('admin.projects.tasks.comments.store', [$project, $task]), [
            'comment' => 'Invalid comment',
        ])->assertNotFound();

        $this->assertDatabaseMissing('project_task_comments', [
            'comment' => 'Invalid comment',
        ]);
    }

    public function test_kanban_card_displays_comment_count_and_modal_content(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Kanban Comment Task',
            'status' => 'review',
        ]);
        ProjectTaskComment::factory()->create([
            'project_task_id' => $task->id,
            'user_id' => auth()->id(),
            'comment' => 'First discussion note',
            'created_at' => now()->subMinute(),
        ]);
        ProjectTaskComment::factory()->create([
            'project_task_id' => $task->id,
            'user_id' => auth()->id(),
            'comment' => 'Second discussion note',
            'created_at' => now(),
        ]);

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']))
            ->assertOk()
            ->assertSee('Kanban Comment Task')
            ->assertSee('💬 2 Comments')
            ->assertSee('Task Discussion')
            ->assertSeeInOrder(['First discussion note', 'Second discussion note'])
            ->assertSee('Tulis komentar...')
            ->assertSee(route('admin.projects.tasks.comments.store', [$project, $task]), false);
    }

    public function test_task_attachment_can_be_uploaded_and_logs_activity(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Attachment Parent Task',
        ]);
        $file = UploadedFile::fake()->create('scope.pdf', 128, 'application/pdf');

        $this->post(route('admin.projects.tasks.attachments.store', [$project, $task]), [
            'attachment' => $file,
            'redirect_tab' => 'kanban',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']));

        $attachment = ProjectTaskAttachment::query()->firstOrFail();

        $this->assertDatabaseHas('project_task_attachments', [
            'project_task_id' => $task->id,
            'user_id' => auth()->id(),
            'original_name' => 'scope.pdf',
            'disk' => 'public',
        ]);
        Storage::disk('public')->assertExists($attachment->path);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'attachment_uploaded',
            'description' => 'Attachment Uploaded: scope.pdf',
        ]);
    }

    public function test_task_attachment_download_requires_attachment_to_belong_to_task(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create(['project_id' => $project->id]);
        $otherTask = ProjectTask::factory()->create(['project_id' => $project->id]);
        $attachment = ProjectTaskAttachment::factory()->create([
            'project_task_id' => $otherTask->id,
            'path' => 'project-task-attachments/other.pdf',
        ]);
        Storage::disk('public')->put($attachment->path, 'fake file');

        $this->get(route('admin.projects.tasks.attachments.download', [$project, $task, $attachment]))
            ->assertNotFound();
    }

    public function test_task_attachment_can_be_deleted_by_uploader_and_logs_activity(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Attachment Delete Task',
        ]);
        $attachment = ProjectTaskAttachment::factory()->create([
            'project_task_id' => $task->id,
            'user_id' => auth()->id(),
            'original_name' => 'delete-me.zip',
            'path' => 'project-task-attachments/delete-me.zip',
        ]);
        Storage::disk('public')->put($attachment->path, 'zip data');

        $this->delete(route('admin.projects.tasks.attachments.destroy', [$project, $task, $attachment]), [
            'redirect_tab' => 'tasks',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']));

        Storage::disk('public')->assertMissing('project-task-attachments/delete-me.zip');
        $this->assertDatabaseMissing('project_task_attachments', [
            'id' => $attachment->id,
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'attachment_deleted',
            'description' => 'Attachment Deleted: delete-me.zip',
        ]);
    }

    public function test_task_attachment_requires_task_to_belong_to_project(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $otherProject->id,
        ]);

        $this->post(route('admin.projects.tasks.attachments.store', [$project, $task]), [
            'attachment' => UploadedFile::fake()->create('invalid.pdf', 12, 'application/pdf'),
        ])->assertNotFound();

        $this->assertDatabaseMissing('project_task_attachments', [
            'original_name' => 'invalid.pdf',
        ]);
    }

    public function test_kanban_card_displays_attachment_count_and_modal_content(): void
    {
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Kanban Attachment Task',
            'status' => 'todo',
        ]);
        ProjectTaskAttachment::factory()->create([
            'project_task_id' => $task->id,
            'user_id' => auth()->id(),
            'original_name' => 'delivery-plan.pdf',
            'file_size' => 1048576,
        ]);

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']))
            ->assertOk()
            ->assertSee('Kanban Attachment Task')
            ->assertSee('📎 Attachments (1)')
            ->assertSee('Task Attachments')
            ->assertSee('delivery-plan.pdf')
            ->assertSee('1.0 MB')
            ->assertSee('Upload File')
            ->assertSee(route('admin.projects.tasks.attachments.store', [$project, $task]), false);
    }

    public function test_done_task_appears_in_done_kanban_column_with_checklist_progress(): void
    {
        $project = Project::factory()->create(['progress' => 100]);
        $task = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Done Task Visible On Kanban',
            'status' => 'done',
            'priority' => 'medium',
            'completed_at' => now(),
        ]);
        ProjectTaskChecklist::factory()->create([
            'project_task_id' => $task->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);
        ProjectTaskChecklist::factory()->create([
            'project_task_id' => $task->id,
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']))
            ->assertOk()
            ->assertSeeInOrder([
                'data-kanban-status="done"',
                'Done Task Visible On Kanban',
                'Completed',
                'Checklist',
                '1/2',
                'Reopen to Todo',
            ], false)
            ->assertSee('<div><span>Done</span><strong>1</strong></div>', false)
            ->assertSee('<div><span>Completion</span><strong>100%</strong></div>', false);
    }

    public function test_done_task_title_is_rendered_on_kanban_tab(): void
    {
        $project = Project::factory()->create(['progress' => 100]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Production Done Task',
            'status' => 'done',
            'completed_at' => now(),
        ]);

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']))
            ->assertOk()
            ->assertSee('data-kanban-status="done"', false)
            ->assertSee('Production Done Task')
            ->assertSee('Completed')
            ->assertSee('Reopen to Todo');
    }

    public function test_project_detail_has_kanban_tab_and_columns(): void
    {
        $project = Project::factory()->create();

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']))
            ->assertOk()
            ->assertSee('project-kanban-workspace', false)
            ->assertSee('Kanban')
            ->assertSee(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']), false)
            ->assertSee('To Do')
            ->assertSee('In Progress')
            ->assertSee('Review')
            ->assertSee('Done')
            ->assertSee('No task');
    }

    public function test_kanban_displays_tasks_by_status_with_card_metadata(): void
    {
        $project = Project::factory()->create();
        $assignee = User::factory()->create(['name' => 'Kanban Owner']);
        $milestone = ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'title' => 'Kanban Milestone',
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'assignee_id' => $assignee->id,
            'title' => 'Kanban Todo Task',
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => now()->subDay()->toDateString(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Kanban Review Task',
            'status' => 'review',
            'priority' => 'critical',
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Kanban Done Task',
            'status' => 'done',
            'priority' => 'low',
            'completed_at' => now(),
        ]);

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']))
            ->assertOk()
            ->assertSeeInOrder(['To Do', 'Kanban Todo Task', 'In Progress'])
            ->assertSeeInOrder(['Review', 'Kanban Review Task', 'Done'])
            ->assertSee('Kanban Owner')
            ->assertSee('Kanban Milestone')
            ->assertSee('High')
            ->assertSee('Critical')
            ->assertSee('Overdue')
            ->assertSee('Move to In Progress')
            ->assertSee('Move to Done')
            ->assertSee('Reopen to Todo');
    }

    public function test_kanban_action_moves_task_status_updates_progress_and_logs_activity(): void
    {
        $project = Project::factory()->create(['progress' => 0]);
        $reviewTask = ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Complete From Kanban',
            'status' => 'review',
            'completed_at' => null,
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'title' => 'Remaining Kanban Task',
            'status' => 'todo',
        ]);

        $this->put(route('admin.projects.tasks.status', [$project, $reviewTask]), [
            'status' => 'done',
            'redirect_tab' => 'kanban',
        ])->assertRedirect(route('admin.projects.show', ['project' => $project, 'tab' => 'kanban']));

        $reviewTask->refresh();
        $this->assertSame('done', $reviewTask->status);
        $this->assertNotNull($reviewTask->completed_at);
        $this->assertSame(50, $project->fresh()->progress);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'task_status_changed',
            'description' => 'Task Status Changed: Complete From Kanban',
        ]);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'event' => 'task_completed',
            'description' => 'Task Completed: Complete From Kanban',
        ]);
    }

    public function test_project_task_index_displays_filters_table_and_actions(): void
    {
        $project = Project::factory()->create([
            'title' => 'Task Index Project',
            'project_number' => 'PRJ-TASK-INDEX',
        ]);
        $assignee = User::factory()->create(['name' => 'Task Index Owner']);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $assignee->id,
            'title' => 'Task Index Row',
            'status' => 'review',
            'priority' => 'high',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);
        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $this->get(route('admin.projects.tasks.index', [
            'q' => 'Task Index',
            'project_id' => $project->id,
            'status' => 'review',
            'priority' => 'high',
            'assignee_id' => $assignee->id,
        ]))
            ->assertOk()
            ->assertSee('Task Management')
            ->assertSee('Total Task')
            ->assertSee('Overdue')
            ->assertSee('All projects')
            ->assertSee('All statuses')
            ->assertSee('All priorities')
            ->assertSee('All assignees')
            ->assertSee('Task Index Project')
            ->assertSee('Task Index Row')
            ->assertSee('Task Index Owner')
            ->assertSee('High')
            ->assertSee('Review')
            ->assertSee('Open Project')
            ->assertSee('Open Detail');
    }

    public function test_project_management_sidebar_tasks_uses_real_route_and_active_state(): void
    {
        $this->get(route('admin.projects.tasks.index'))
            ->assertOk()
            ->assertSee(route('admin.projects.tasks.index'), false)
            ->assertSee('Task Management')
            ->assertSee('Projects</span>', false)
            ->assertSee('Tasks</span>', false);
    }

    public function test_project_show_displays_foundation_overview_tabs_and_timeline(): void
    {
        [$customer, $lead, $opportunity, $quotation] = $this->wonDealSource();
        $manager = User::factory()->create(['name' => 'Overview PM']);
        $project = Project::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'project_manager_id' => $manager->id,
            'title' => 'Overview Foundation Project',
            'budget' => 88000000,
        ]);
        ProjectActivityLog::create([
            'project_id' => $project->id,
            'event' => 'project_created',
            'description' => 'Project Created',
        ]);

        $this->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('PROJECT WORKSPACE')
            ->assertSee('Project Number')
            ->assertSee('Project Name')
            ->assertSee('Customer')
            ->assertSee('Lead')
            ->assertSee('Opportunity')
            ->assertSee('Quotation')
            ->assertSee('Deal')
            ->assertSee('Budget')
            ->assertSee('Start Date')
            ->assertSee('Due Date')
            ->assertSee('Remaining Days')
            ->assertSee('Status')
            ->assertSee('Progress')
            ->assertSee('Project Manager')
            ->assertSee('Overview PM')
            ->assertSee('Members')
            ->assertSee('Milestones')
            ->assertSee('Timeline')
            ->assertSee('Files')
            ->assertSee('Notes')
            ->assertSee('Activity')
            ->assertSee('Tasks')
            ->assertSee('Related Records')
            ->assertSee('Quick Stats')
            ->assertSee('Quick Actions')
            ->assertSee('Open Customer')
            ->assertSee('Open Lead')
            ->assertSee('Open Opportunity')
            ->assertSee('Open Quotation')
            ->assertSee('Project Created');

        $this->get(route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']))
            ->assertOk()
            ->assertSee('Total Task')
            ->assertSee('Belum ada Task')
            ->assertSee('Mulai pecah pekerjaan project menjadi task delivery.');
    }

    public function test_project_dashboard_displays_portfolio_sections(): void
    {
        $manager = User::factory()->create([
            'name' => 'Dashboard PM',
            'email' => 'dashboard-pm@example.com',
        ]);
        $activeProject = Project::factory()->create([
            'title' => 'Active Dashboard Project',
            'status' => 'active',
            'progress' => 80,
            'project_manager_id' => $manager->id,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);
        Project::factory()->create([
            'title' => 'Completed Dashboard Project',
            'status' => 'completed',
            'progress' => 100,
        ]);
        Project::factory()->create([
            'title' => 'Delayed Dashboard Project',
            'status' => 'active',
            'progress' => 40,
            'due_date' => now()->subDay()->toDateString(),
        ]);
        ProjectMilestone::factory()->create([
            'project_id' => $activeProject->id,
            'title' => 'Dashboard Go Live',
            'status' => 'pending',
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        ProjectActivityLog::create([
            'project_id' => $activeProject->id,
            'actor_id' => $manager->id,
            'event' => 'project_updated',
            'description' => 'Dashboard Activity',
        ]);

        $this->get(route('admin.projects.dashboard'))
            ->assertOk()
            ->assertSee('+ New Project')
            ->assertSee(route('admin.projects.create'), false)
            ->assertSee('Project Dashboard')
            ->assertSee('Total Projects')
            ->assertSee('All delivery records')
            ->assertSee('Active')
            ->assertSee('Completed')
            ->assertSee('Delayed')
            ->assertSee('Overall Progress')
            ->assertSee('Progress Overview')
            ->assertSee('Project Status')
            ->assertSee('Recent Activities')
            ->assertSee('Dashboard Activity')
            ->assertSee('Upcoming Milestones')
            ->assertSee('Dashboard Go Live')
            ->assertSee('Project Managers')
            ->assertSee('Dashboard PM')
            ->assertSee('Latest Projects')
            ->assertSee('Active Dashboard Project');
    }

    public function test_project_dashboard_displays_compact_empty_state_without_widgets(): void
    {
        $this->get(route('admin.projects.dashboard'))
            ->assertOk()
            ->assertSee('Project Dashboard')
            ->assertSee('Belum ada Project')
            ->assertSee('Mulai dengan membuat project pertama atau ubah Deal Won menjadi Project.')
            ->assertSee('Create Project')
            ->assertDontSee('Progress Overview')
            ->assertDontSee('Latest Projects')
            ->assertDontSee('No project statistics available.');
    }

    public function test_project_index_displays_lead_style_workspace(): void
    {
        [$customer, , $opportunity, $quotation] = $this->wonDealSource();
        Project::factory()->create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'quotation_id' => $quotation->id,
            'title' => 'Lead Style Project',
            'status' => 'active',
            'budget' => 88000000,
            'progress' => 75,
        ]);

        $this->get(route('admin.projects.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Project workspace')
            ->assertSee('+ Add Project')
            ->assertSee('Total Project')
            ->assertSee('Average Progress')
            ->assertSee('Semua')
            ->assertSee('Project Manager')
            ->assertSee('Last Update')
            ->assertSee('Action')
            ->assertSee('Lead Style Project')
            ->assertSee('Opportunity: '.$opportunity->title)
            ->assertSee('Quotation: '.$quotation->quote_number)
            ->assertSee('Customer: '.$customer->name)
            ->assertSee('75%')
            ->assertSee('View')
            ->assertSee('Edit');
    }

    /**
     * @return array{0:Customer,1:Lead,2:Opportunity,3:Quotation}
     */
    protected function wonDealSource(): array
    {
        $customer = Customer::factory()->create(['name' => 'Project Source Customer']);
        $lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Project Source Lead',
        ]);
        $opportunity = Opportunity::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'title' => 'Project Source Opportunity',
            'status' => 'won',
            'probability' => 100,
            'won_at' => now(),
        ]);
        $quotation = Quotation::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QTN-PROJECT-SOURCE-001',
            'title' => 'Project Source Quotation',
            'status' => 'accepted',
            'amount' => 88000000,
        ]);

        return [$customer, $lead, $opportunity, $quotation];
    }

    /**
     * @return array{0:Customer,1:Lead,2:Opportunity,3:Quotation,4:WhatsAppConversation,5:Ticket}
     */
    protected function wonDealSourceWithConversation(): array
    {
        [$customer, $lead, $opportunity, $quotation] = $this->wonDealSource();
        $conversation = WhatsAppConversation::create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'contact_name' => 'Project Omni Contact',
            'phone_number' => '6281777777000',
            'channel' => 'whatsapp',
            'last_message' => 'Deal won project',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'phone' => '6281777777000',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Deal won project',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);
        $lead->update(['conversation_id' => $conversation->id]);
        $opportunity->update(['conversation_id' => $conversation->id]);
        $quotation->update(['conversation_id' => $conversation->id]);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'ticket_number' => 'TCK-PROJECT-OMNI-001',
            'status' => 'open',
        ]);

        return [$customer, $lead, $opportunity, $quotation, $conversation, $ticket];
    }
}
