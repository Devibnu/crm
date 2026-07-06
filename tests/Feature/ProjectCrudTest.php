<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('name="customer_id" value="'.$customer->id.'"', false)
            ->assertSee('name="lead_id" value="'.$lead->id.'"', false)
            ->assertSee('name="opportunity_id" value="'.$opportunity->id.'"', false)
            ->assertSee('name="quotation_id" value="'.$quotation->id.'"', false)
            ->assertSee('value="'.$quotation->title.'"', false)
            ->assertSee('value="88000000.00"', false)
            ->assertSee('Created from Deal Won.')
            ->assertSee('Quotation: '.$quotation->quote_number)
            ->assertSee('Opportunity: '.$opportunity->title)
            ->assertSee('Customer: '.$customer->name);
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
            ->assertSee('Project Created');
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
            ->assertSee('Total Project')
            ->assertSee('Active')
            ->assertSee('Completed')
            ->assertSee('Delayed')
            ->assertSee('Overall Progress')
            ->assertSee('Progress Chart')
            ->assertSee('Project Status Chart')
            ->assertSee('Recent Activities')
            ->assertSee('Dashboard Activity')
            ->assertSee('Upcoming Milestones')
            ->assertSee('Dashboard Go Live')
            ->assertSee('Project Managers')
            ->assertSee('Dashboard PM')
            ->assertSee('Latest Projects')
            ->assertSee('Active Dashboard Project');
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
            ->assertSee('Total Projects')
            ->assertSee('Average Progress')
            ->assertSee('All statuses')
            ->assertSee('Created Date')
            ->assertSee('Action')
            ->assertSee('Lead Style Project')
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
