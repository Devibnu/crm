<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use App\Models\User;
use App\Services\Projects\ProjectActivityLogger;
use App\Services\Projects\ProjectProgressEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectActivityLogger $activityLogger,
        protected ProjectProgressEngine $progressEngine,
    ) {
    }

    public function dashboard(): View
    {
        $totalProjects = Project::query()->count();
        $activeProjects = Project::query()->where('status', 'active')->count();
        $completedProjects = Project::query()->where('status', 'completed')->count();
        $delayedProjects = Project::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        $averageProgress = (int) round((float) Project::query()->avg('progress'));
        $totalMilestones = ProjectMilestone::query()->count();
        $openMilestones = ProjectMilestone::query()->whereIn('status', ['pending', 'planning', 'in_progress'])->count();
        $completedMilestones = ProjectMilestone::query()->where('status', 'completed')->count();
        $delayedMilestones = ProjectMilestone::query()
            ->where(function ($query): void {
                $query
                    ->where('status', 'delayed')
                    ->orWhere(function ($overdue): void {
                        $overdue
                            ->whereDate('due_date', '<', now()->toDateString())
                            ->whereNotIn('status', ['completed', 'cancelled']);
                    });
            })
            ->count();

        $statusCounts = Project::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status');

        $managerCounts = Project::query()
            ->whereNotNull('project_manager_id')
            ->selectRaw('project_manager_id, COUNT(*) as total')
            ->groupBy('project_manager_id')
            ->pluck('total', 'project_manager_id');

        $projectManagers = User::query()
            ->whereIn('id', $managerCounts->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'user' => $user,
                'total' => (int) $managerCounts->get($user->id, 0),
            ]);

        return view('admin.projects.dashboard', [
            'totalProjects' => $totalProjects,
            'activeProjects' => $activeProjects,
            'completedProjects' => $completedProjects,
            'delayedProjects' => $delayedProjects,
            'averageProgress' => $averageProgress,
            'hasProjects' => $totalProjects > 0,
            'dashboardKpis' => [
                ['icon' => 'case', 'title' => 'Total Projects', 'value' => $totalProjects, 'helper' => 'All delivery records'],
                ['icon' => 'activity', 'title' => 'Active', 'value' => $activeProjects, 'helper' => 'Currently running'],
                ['icon' => 'deal', 'title' => 'Completed', 'value' => $completedProjects, 'helper' => 'Closed successfully'],
                ['icon' => 'timer', 'title' => 'Delayed', 'value' => $delayedProjects, 'helper' => 'Past due and open'],
                ['icon' => 'analysis', 'title' => 'Overall Progress', 'value' => $averageProgress.'%', 'helper' => 'Average milestone progress'],
                ['icon' => 'calendar', 'title' => 'Milestones', 'value' => $totalMilestones, 'helper' => $openMilestones.' open / '.$completedMilestones.' done'],
            ],
            'milestoneSummary' => [
                'total' => $totalMilestones,
                'open' => $openMilestones,
                'completed' => $completedMilestones,
                'delayed' => $delayedMilestones,
            ],
            'statusCounts' => $statusCounts,
            'progressBuckets' => [
                '0-25%' => Project::query()->whereBetween('progress', [0, 25])->count(),
                '26-50%' => Project::query()->whereBetween('progress', [26, 50])->count(),
                '51-75%' => Project::query()->whereBetween('progress', [51, 75])->count(),
                '76-100%' => Project::query()->whereBetween('progress', [76, 100])->count(),
            ],
            'recentActivities' => ProjectActivityLog::query()
                ->with(['project:id,project_number,title', 'actor:id,name'])
                ->latest()
                ->limit(6)
                ->get(),
            'upcomingMilestones' => ProjectMilestone::query()
                ->with('project:id,project_number,title')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '>=', now()->toDateString())
                ->where('status', '!=', 'completed')
                ->orderBy('due_date')
                ->limit(6)
                ->get(),
            'projectManagers' => $projectManagers,
            'recentProjects' => Project::query()
                ->with(['customer:id,name', 'projectManager:id,name'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $selectedStatus = (string) $request->query('status', '');
        $statusOptions = $this->statusOptions();

        $projects = Project::query()
            ->with(['customer:id,name', 'opportunity:id,title', 'quotation:id,quote_number', 'projectManager:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('project_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('opportunity', fn ($opportunity) => $opportunity->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('quotation', fn ($quotation) => $quotation->where('quote_number', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($selectedStatus, $statusOptions, true), fn ($query) => $query->where('status', $selectedStatus))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'statusOptions' => $statusOptions,
            'summary' => [
                'total' => Project::query()->count(),
                'active' => Project::query()->where('status', 'active')->count(),
                'completed' => Project::query()->where('status', 'completed')->count(),
                'average_progress' => (int) round((float) Project::query()->avg('progress')),
            ],
        ]);
    }

    public function taskIndex(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $selectedProject = (string) $request->query('project_id', '');
        $selectedStatus = (string) $request->query('status', '');
        $selectedPriority = (string) $request->query('priority', '');
        $selectedAssignee = (string) $request->query('assignee_id', '');
        $selectedMilestone = (string) $request->query('milestone_id', '');
        $taskStatuses = $this->taskStatusOptions();
        $taskPriorities = $this->taskPriorityOptions();

        $tasks = ProjectTask::query()
            ->with(['project:id,project_number,title,progress', 'milestone:id,project_id,title,status,color,icon', 'assignee:id,name,email'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($project) use ($search): void {
                            $project
                                ->where('title', 'like', "%{$search}%")
                                ->orWhere('project_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($selectedProject !== '', fn ($query) => $query->where('project_id', (int) $selectedProject))
            ->when(array_key_exists($selectedStatus, $taskStatuses), fn ($query) => $query->where('status', $selectedStatus))
            ->when(array_key_exists($selectedPriority, $taskPriorities), fn ($query) => $query->where('priority', $selectedPriority))
            ->when($selectedAssignee !== '', fn ($query) => $query->where('assignee_id', (int) $selectedAssignee))
            ->when($selectedMilestone !== '', fn ($query) => $query->where('milestone_id', (int) $selectedMilestone))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.projects.tasks.index', [
            'tasks' => $tasks,
            'search' => $search,
            'selectedProject' => $selectedProject,
            'selectedStatus' => $selectedStatus,
            'selectedPriority' => $selectedPriority,
            'selectedAssignee' => $selectedAssignee,
            'selectedMilestone' => $selectedMilestone,
            'taskStatuses' => $taskStatuses,
            'taskPriorities' => $taskPriorities,
            'projects' => Project::query()->orderBy('title')->get(['id', 'project_number', 'title']),
            'milestones' => ProjectMilestone::query()
                ->with('project:id,project_number,title')
                ->orderBy('due_date')
                ->orderBy('title')
                ->get(['id', 'project_id', 'title', 'status', 'due_date']),
            'assignees' => User::query()
                ->whereIn('id', ProjectTask::query()->select('assignee_id')->whereNotNull('assignee_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'summary' => [
                'total' => ProjectTask::query()->count(),
                'todo' => ProjectTask::query()->where('status', 'todo')->count(),
                'in_progress' => ProjectTask::query()->where('status', 'in_progress')->count(),
                'review' => ProjectTask::query()->where('status', 'review')->count(),
                'done' => ProjectTask::query()->where('status', 'done')->count(),
                'overdue' => ProjectTask::query()
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->where('status', '!=', 'done')
                    ->count(),
            ],
        ]);
    }

    public function milestoneIndex(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $selectedProject = (string) $request->query('project_id', '');
        $selectedStatus = (string) $request->query('status', '');
        $statusOptions = $this->milestoneStatusOptions();

        $milestones = ProjectMilestone::query()
            ->with(['project:id,project_number,title', 'tasks:id,project_id,milestone_id,status,due_date,completed_at'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($project) use ($search): void {
                            $project
                                ->where('title', 'like', "%{$search}%")
                                ->orWhere('project_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($selectedProject !== '', fn ($query) => $query->where('project_id', (int) $selectedProject))
            ->when(array_key_exists($selectedStatus, $statusOptions), fn ($query) => $query->where('status', $selectedStatus))
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $projects = Project::query()->orderBy('title')->get(['id', 'project_number', 'title']);
        $totalMilestones = ProjectMilestone::query()->count();
        $planningMilestones = ProjectMilestone::query()->whereIn('status', ['pending', 'planning'])->count();
        $inProgressMilestones = ProjectMilestone::query()->where('status', 'in_progress')->count();
        $completedMilestones = ProjectMilestone::query()->where('status', 'completed')->count();
        $completionPercentage = $totalMilestones === 0
            ? 0
            : (int) round(($completedMilestones / $totalMilestones) * 100);

        return view('admin.projects.milestones.index', [
            'milestones' => $milestones,
            'search' => $search,
            'selectedProject' => $selectedProject,
            'selectedStatus' => $selectedStatus,
            'statusOptions' => $statusOptions,
            'projects' => $projects,
            'createMilestoneProject' => $selectedProject !== ''
                ? $projects->firstWhere('id', (int) $selectedProject)
                : $projects->first(),
            'summary' => [
                'total' => $totalMilestones,
                'planning' => $planningMilestones,
                'in_progress' => $inProgressMilestones,
                'completed' => $completedMilestones,
                'delayed' => ProjectMilestone::query()
                    ->where(function ($query): void {
                        $query
                            ->where('status', 'delayed')
                            ->orWhere(function ($overdue): void {
                                $overdue
                                    ->whereDate('due_date', '<', now()->toDateString())
                                    ->whereNotIn('status', ['completed', 'cancelled']);
                            });
                    })
                    ->count(),
                'completion_percentage' => $completionPercentage,
            ],
        ]);
    }

    public function createMilestone(Project $project): View
    {
        return view('admin.projects.milestones.form', [
            'project' => $project,
            'milestone' => new ProjectMilestone([
                'project_id' => $project->id,
                'status' => ProjectMilestone::STATUS_PLANNING,
                'color' => 'blue',
                'icon' => 'calendar',
            ]),
            'statusOptions' => $this->milestoneStatusOptions(),
            'colorOptions' => $this->milestoneColorOptions(),
            'iconOptions' => $this->milestoneIconOptions(),
            'formMode' => 'create',
        ]);
    }

    public function showMilestone(Project $project, ProjectMilestone $milestone): View
    {
        abort_unless($milestone->project_id === $project->id, 404);

        return view('admin.projects.milestones.show', [
            'project' => $project,
            'milestone' => $milestone->load(['project:id,project_number,title', 'tasks.assignee:id,name,email']),
            'siblingMilestones' => $project->milestones()
                ->whereKeyNot($milestone->id)
                ->get(['id', 'title']),
            'statusOptions' => $this->milestoneStatusOptions(),
            'taskStatusOptions' => $this->taskStatusOptions(),
            'taskPriorityOptions' => $this->taskPriorityOptions(),
        ]);
    }

    public function editMilestone(Project $project, ProjectMilestone $milestone): View
    {
        abort_unless($milestone->project_id === $project->id, 404);

        return view('admin.projects.milestones.form', [
            'project' => $project,
            'milestone' => $milestone,
            'statusOptions' => $this->milestoneStatusOptions(),
            'colorOptions' => $this->milestoneColorOptions(),
            'iconOptions' => $this->milestoneIconOptions(),
            'formMode' => 'edit',
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $project = new Project([
            'status' => 'planning',
            'budget' => 0,
            'progress' => 0,
        ]);
        $sourceQuotation = null;

        if ($request->filled('quotation_id')) {
            $sourceQuotation = Quotation::query()
                ->with(['customer:id,name', 'lead:id,name', 'opportunity:id,title'])
                ->find($request->integer('quotation_id'));

            if ($sourceQuotation) {
                $existingProject = Project::query()
                    ->where('quotation_id', $sourceQuotation->id)
                    ->first();

                if ($existingProject) {
                    return redirect()
                        ->route('admin.projects.show', $existingProject)
                        ->with('success', 'Project untuk quotation ini sudah ada.');
                }

                $project = new Project([
                    'customer_id' => $sourceQuotation->customer_id,
                    'lead_id' => $sourceQuotation->lead_id,
                    'opportunity_id' => $sourceQuotation->opportunity_id,
                    'quotation_id' => $sourceQuotation->id,
                    'title' => $sourceQuotation->title ?: $sourceQuotation->opportunity?->title,
                    'description' => $this->descriptionFromQuotation($sourceQuotation),
                    'status' => 'planning',
                    'budget' => $sourceQuotation->amount ?? 0,
                    'progress' => 0,
                ]);
            }
        }

        return view('admin.projects.create', [
            'project' => $project,
            'sourceQuotation' => $sourceQuotation,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'leads' => Lead::query()->orderBy('name')->get(['id', 'name']),
            'opportunities' => Opportunity::query()->orderBy('title')->get(['id', 'title']),
            'quotations' => Quotation::query()->orderByDesc('id')->get(['id', 'quote_number', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('quotation_id')) {
            $existingProject = Project::query()
                ->where('quotation_id', $request->integer('quotation_id'))
                ->first();

            if ($existingProject) {
                return redirect()
                    ->route('admin.projects.show', $existingProject)
                    ->with('success', 'Project untuk quotation ini sudah ada.');
            }
        }

        $validated = $this->validatedData($request);

        $validated['project_number'] = $this->generateProjectNumber();
        $validated['created_by'] = auth()->id();

        $project = Project::create($validated);
        $this->progressEngine->refresh($project);
        $this->activityLogger->log($project, 'project_created', 'Project Created', $project);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Project berhasil dibuat dari deal.');
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.edit', [
            'project' => $project,
            'sourceQuotation' => $project->quotation,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'leads' => Lead::query()->orderBy('name')->get(['id', 'name']),
            'opportunities' => Opportunity::query()->orderBy('title')->get(['id', 'title']),
            'quotations' => Quotation::query()->orderByDesc('id')->get(['id', 'quote_number', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validatedData($request, $project);
        $oldStatus = $project->status;

        $project->update($validated);
        $this->progressEngine->refresh($project);

        $this->activityLogger->log($project, 'project_updated', 'Project Updated', $project);

        if ($oldStatus !== $project->status) {
            $this->activityLogger->log($project, 'status_changed', 'Status Changed: '.str($oldStatus)->headline().' to '.str($project->status)->headline(), $project, [
                'from' => $oldStatus,
                'to' => $project->status,
            ]);
        }

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Project berhasil diperbarui.');
    }

    public function show(Project $project): View
    {
        $activeTab = request('tab', 'overview');
        $allowedTabs = ['overview', 'members', 'milestones', 'timeline', 'tasks', 'kanban', 'files', 'notes', 'activity'];
        $activeTab = in_array($activeTab, $allowedTabs, true) ? $activeTab : 'overview';

        return view('admin.projects.show', [
            'project' => $project->load([
                'customer:id,name',
                'lead:id,name',
                'opportunity:id,title,status,won_at,lost_at',
                'quotation:id,quote_number,title,status,amount',
                'creator:id,name',
                'projectManager:id,name,email',
                'members.user:id,name,email',
                'milestones.tasks:id,project_id,milestone_id,status,due_date,completed_at',
                'tasks.milestone:id,title,color,icon,status',
                'tasks.assignee:id,name,email',
                'tasks.checklists.completedBy:id,name',
                'tasks.comments.user:id,name,email',
                'tasks.attachments.user:id,name,email',
                'activityLogs.actor:id,name',
            ]),
            'activeTab' => $activeTab,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'memberRoles' => $this->memberRoleOptions(),
            'milestoneStatusOptions' => $this->milestoneStatusOptions(),
            'taskStatusOptions' => $this->taskStatusOptions(),
            'taskPriorityOptions' => $this->taskPriorityOptions(),
        ]);
    }

    public function storeMember(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::unique('project_members')->where('project_id', $project->id)],
            'role' => ['required', Rule::in(array_keys($this->memberRoleOptions()))],
        ]);

        $member = $project->members()->create($validated);
        $member->load('user:id,name');

        $this->activityLogger->log($project, 'member_added', 'Member Added: '.$member->user?->name, $member, [
            'role' => $member->role,
        ]);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Member berhasil ditambahkan.');
    }

    public function destroyMember(Project $project, ProjectMember $member): RedirectResponse
    {
        abort_unless($member->project_id === $project->id, 404);

        $member->load('user:id,name');
        $description = 'Member Removed: '.($member->user?->name ?: '-');
        $member->delete();

        $this->activityLogger->log($project, 'member_removed', $description, null);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Member berhasil dihapus.');
    }

    public function storeMilestone(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validatedMilestoneData($request);
        $validated['sort_order'] = (int) $project->milestones()->max('sort_order') + 1;
        $validated['completed_at'] = $validated['status'] === 'completed' ? now() : null;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $milestone = $project->milestones()->create($validated);
        $this->progressEngine->refresh($project);

        $event = $milestone->status === 'completed' ? 'milestone_completed' : 'milestone_created';
        $description = $milestone->status === 'completed'
            ? 'Milestone Completed: '.$milestone->title
            : 'Milestone Created: '.$milestone->title;

        $this->activityLogger->log($project, $event, $description, $milestone);

        $redirectRoute = $request->input('redirect_to') === 'milestone'
            ? route('admin.projects.milestones.show', [$project, $milestone])
            : route('admin.projects.show', $project);

        return redirect($redirectRoute)->with('success', 'Milestone berhasil ditambahkan.');
    }

    public function updateMilestone(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);

        $validated = $this->validatedMilestoneData($request);

        $oldStatus = $milestone->status;
        $validated['completed_at'] = $validated['status'] === 'completed'
            ? ($milestone->completed_at ?: now())
            : null;
        $validated['updated_by'] = auth()->id();

        $milestone->update($validated);
        $this->progressEngine->refresh($project);

        $event = $oldStatus !== 'completed' && $milestone->status === 'completed'
            ? 'milestone_completed'
            : 'milestone_updated';
        $description = $event === 'milestone_completed'
            ? 'Milestone Completed: '.$milestone->title
            : 'Milestone Updated: '.$milestone->title;

        $this->activityLogger->log($project, $event, $description, $milestone, [
            'from_status' => $oldStatus,
            'to_status' => $milestone->status,
        ]);

        $redirectRoute = $request->input('redirect_to') === 'milestone'
            ? route('admin.projects.milestones.show', [$project, $milestone])
            : route('admin.projects.show', $project);

        return redirect($redirectRoute)->with('success', 'Milestone berhasil diperbarui.');
    }

    public function destroyMilestone(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);

        $validated = $request->validate([
            'task_action' => ['required', Rule::in(['delete_tasks', 'move'])],
            'target_milestone_id' => [
                'nullable',
                'required_if:task_action,move',
                Rule::exists('project_milestones', 'id')->where(
                    fn ($query) => $query->where('project_id', $project->id)->where('id', '!=', $milestone->id)
                ),
            ],
        ]);

        $taskCount = $milestone->tasks()->count();

        if ($validated['task_action'] === 'move' && $validated['target_milestone_id']) {
            $milestone->tasks()->update(['milestone_id' => $validated['target_milestone_id']]);
            $targetMilestone = ProjectMilestone::query()->find($validated['target_milestone_id']);

            if ($targetMilestone) {
                $this->progressEngine->refreshMilestone($targetMilestone);
            }
        } else {
            $milestone->tasks()->get()->each->delete();
        }

        $this->activityLogger->log($project, 'milestone_deleted', 'Milestone Deleted: '.$milestone->title, $milestone, [
            'task_action' => $validated['task_action'],
            'task_count' => $taskCount,
        ]);

        $milestone->delete();
        $this->progressEngine->refresh($project);

        return redirect()
            ->route('admin.projects.milestones.index')
            ->with('success', 'Milestone berhasil dihapus.');
    }

    public function storeTask(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'milestone_id' => [
                'nullable',
                Rule::exists('project_milestones', 'id')->where('project_id', $project->id),
            ],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(array_keys($this->taskStatusOptions()))],
            'priority' => ['nullable', Rule::in(array_keys($this->taskPriorityOptions()))],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['status'] = $validated['status'] ?? 'todo';
        $validated['priority'] = $validated['priority'] ?? 'medium';
        $validated['sort_order'] = (int) $project->tasks()->max('sort_order') + 1;
        $validated['completed_at'] = $validated['status'] === 'done' ? now() : null;

        $task = $project->tasks()->create($validated);

        if ($task->milestone_id) {
            $this->progressEngine->refreshMilestone($task->milestone);
        }

        $this->progressEngine->refresh($project);

        $this->activityLogger->log($project, 'task_created', 'Task Created: '.$task->title, $task, [
            'status' => $task->status,
            'priority' => $task->priority,
        ]);

        if ($task->status === 'done') {
            $this->activityLogger->log($project, 'task_completed', 'Task Completed: '.$task->title, $task);
        }

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => 'tasks'])
            ->with('success', 'Task berhasil ditambahkan.');
    }

    public function updateTaskStatus(Request $request, Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->taskStatusOptions()))],
            'redirect_tab' => ['nullable', Rule::in(['tasks', 'kanban'])],
        ]);

        $oldStatus = $task->status;
        $milestone = $task->milestone;
        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done'
                ? ($task->completed_at ?: now())
                : null,
        ]);

        if ($milestone) {
            $this->progressEngine->refreshMilestone($milestone);
        }

        $this->progressEngine->refresh($project);

        if ($oldStatus !== $task->status) {
            $this->activityLogger->log($project, 'task_status_changed', 'Task Status Changed: '.$task->title, $task, [
                'from_status' => $oldStatus,
                'to_status' => $task->status,
            ]);

            if ($task->status === 'done') {
                $this->activityLogger->log($project, 'task_completed', 'Task Completed: '.$task->title, $task);
            }
        }

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => $validated['redirect_tab'] ?? 'tasks'])
            ->with('success', 'Status task berhasil diperbarui.');
    }

    public function storeTaskChecklist(Request $request, Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'redirect_tab' => ['nullable', Rule::in(['tasks', 'kanban'])],
        ]);

        $checklist = $task->checklists()->create([
            'title' => $validated['title'],
            'sort_order' => (int) $task->checklists()->max('sort_order') + 1,
        ]);

        $this->activityLogger->log($project, 'checklist_created', 'Checklist Created: '.$checklist->title, $checklist, [
            'task_id' => $task->id,
            'task_title' => $task->title,
        ]);

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => $validated['redirect_tab'] ?? 'tasks'])
            ->with('success', 'Checklist berhasil ditambahkan.');
    }

    public function toggleTaskChecklist(Request $request, Project $project, ProjectTask $task, ProjectTaskChecklist $checklist): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);
        abort_unless($checklist->project_task_id === $task->id, 404);

        $validated = $request->validate([
            'redirect_tab' => ['nullable', Rule::in(['tasks', 'kanban'])],
        ]);

        if ($checklist->is_completed) {
            $checklist->update([
                'is_completed' => false,
                'completed_at' => null,
                'completed_by' => null,
            ]);

            $this->activityLogger->log($project, 'checklist_reopened', 'Checklist Reopened: '.$checklist->title, $checklist, [
                'task_id' => $task->id,
                'task_title' => $task->title,
            ]);
        } else {
            $checklist->update([
                'is_completed' => true,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);

            $this->activityLogger->log($project, 'checklist_completed', 'Checklist Completed: '.$checklist->title, $checklist, [
                'task_id' => $task->id,
                'task_title' => $task->title,
            ]);
        }

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => $validated['redirect_tab'] ?? 'tasks'])
            ->with('success', 'Checklist berhasil diperbarui.');
    }

    public function storeTaskComment(Request $request, Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
            'redirect_tab' => ['nullable', Rule::in(['tasks', 'kanban'])],
        ]);

        $comment = $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
        ]);

        $this->activityLogger->log($project, 'comment_added', 'Comment Added: '.$task->title, $comment, [
            'task_id' => $task->id,
            'task_title' => $task->title,
        ]);

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => $validated['redirect_tab'] ?? 'tasks'])
            ->with('success', 'Comment berhasil ditambahkan.');
    }

    public function updateTaskComment(Request $request, Project $project, ProjectTask $task, ProjectTaskComment $comment): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);
        abort_unless($comment->project_task_id === $task->id, 404);
        abort_unless($this->canManageTaskComment($comment), 403);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
            'redirect_tab' => ['nullable', Rule::in(['tasks', 'kanban'])],
        ]);

        $comment->update([
            'comment' => $validated['comment'],
        ]);

        $this->activityLogger->log($project, 'comment_updated', 'Comment Updated: '.$task->title, $comment, [
            'task_id' => $task->id,
            'task_title' => $task->title,
        ]);

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => $validated['redirect_tab'] ?? 'tasks'])
            ->with('success', 'Comment berhasil diperbarui.');
    }

    public function destroyTaskComment(Request $request, Project $project, ProjectTask $task, ProjectTaskComment $comment): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);
        abort_unless($comment->project_task_id === $task->id, 404);
        abort_unless($this->canManageTaskComment($comment), 403);

        $validated = $request->validate([
            'redirect_tab' => ['nullable', Rule::in(['tasks', 'kanban'])],
        ]);

        $this->activityLogger->log($project, 'comment_deleted', 'Comment Deleted: '.$task->title, $comment, [
            'task_id' => $task->id,
            'task_title' => $task->title,
        ]);

        $comment->delete();

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => $validated['redirect_tab'] ?? 'tasks'])
            ->with('success', 'Comment berhasil dihapus.');
    }

    public function storeTaskAttachment(Request $request, Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'attachment' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip'],
            'redirect_tab' => ['nullable', Rule::in(['tasks', 'kanban'])],
        ]);

        $file = $validated['attachment'];
        $path = $file->store('project-task-attachments', 'public');
        $storedName = basename($path);

        $attachment = $task->attachments()->create([
            'user_id' => auth()->id(),
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'disk' => 'public',
            'path' => $path,
        ]);

        $this->activityLogger->log($project, 'attachment_uploaded', 'Attachment Uploaded: '.$attachment->original_name, $attachment, [
            'task_id' => $task->id,
            'task_title' => $task->title,
        ]);

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => $validated['redirect_tab'] ?? 'tasks'])
            ->with('success', 'Attachment berhasil diunggah.');
    }

    public function downloadTaskAttachment(Project $project, ProjectTask $task, ProjectTaskAttachment $attachment)
    {
        abort_unless($task->project_id === $project->id, 404);
        abort_unless($attachment->project_task_id === $task->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroyTaskAttachment(Request $request, Project $project, ProjectTask $task, ProjectTaskAttachment $attachment): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);
        abort_unless($attachment->project_task_id === $task->id, 404);
        abort_unless($this->canManageTaskAttachment($attachment), 403);

        $validated = $request->validate([
            'redirect_tab' => ['nullable', Rule::in(['tasks', 'kanban'])],
        ]);

        $this->activityLogger->log($project, 'attachment_deleted', 'Attachment Deleted: '.$attachment->original_name, $attachment, [
            'task_id' => $task->id,
            'task_title' => $task->title,
        ]);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return redirect()
            ->route('admin.projects.show', ['project' => $project, 'tab' => $validated['redirect_tab'] ?? 'tasks'])
            ->with('success', 'Attachment berhasil dihapus.');
    }

    protected function canManageTaskComment(ProjectTaskComment $comment): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $comment->user_id === $user->id || $user->hasRole(['admin', 'Admin', 'super_admin']);
    }

    protected function canManageTaskAttachment(ProjectTaskAttachment $attachment): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $attachment->user_id === $user->id || $user->hasRole(['admin', 'Admin', 'super_admin']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request, ?Project $project = null): array
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'quotation_id' => [
                'nullable',
                'exists:quotations,id',
                Rule::unique('projects', 'quotation_id')->ignore($project?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $validated['budget'] = $validated['budget'] ?? 0;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedMilestoneData(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', Rule::in(array_keys($this->milestoneColorOptions()))],
            'icon' => ['nullable', Rule::in(array_keys($this->milestoneIconOptions()))],
            'status' => ['required', Rule::in(array_keys($this->milestoneStatusOptions()))],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['color'] = $validated['color'] ?? 'blue';
        $validated['icon'] = $validated['icon'] ?? 'calendar';

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['planning', 'active', 'on_hold', 'completed', 'cancelled', 'maintenance'];
    }

    /**
     * @return array<string, string>
     */
    protected function memberRoleOptions(): array
    {
        return [
            'project_manager' => 'Project Manager',
            'developer' => 'Developer',
            'designer' => 'Designer',
            'qa' => 'QA',
            'support' => 'Support',
            'observer' => 'Observer',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function milestoneStatusOptions(): array
    {
        return [
            'planning' => 'Planning',
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'delayed' => 'Delayed',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function milestoneColorOptions(): array
    {
        return [
            'blue' => 'Blue',
            'green' => 'Green',
            'amber' => 'Amber',
            'violet' => 'Violet',
            'rose' => 'Rose',
            'slate' => 'Slate',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function milestoneIconOptions(): array
    {
        return [
            'calendar' => 'Calendar',
            'kanban' => 'Kanban',
            'activity' => 'Activity',
            'deal' => 'Deal',
            'case' => 'Case',
            'book' => 'Book',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function taskStatusOptions(): array
    {
        return [
            'todo' => 'Todo',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'done' => 'Done',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function taskPriorityOptions(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }

    protected function descriptionFromQuotation(Quotation $quotation): string
    {
        return collect([
            'Created from Deal Won.',
            'Quotation: '.$quotation->quote_number,
            'Opportunity: '.($quotation->opportunity?->title ?: '-'),
            'Customer: '.($quotation->customer?->name ?: '-'),
        ])->implode("\n");
    }

    protected function generateProjectNumber(): string
    {
        $prefix = 'PRJ-'.now()->format('Ymd');
        $sequence = 1;

        do {
            $projectNumber = $prefix.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Project::query()->where('project_number', $projectNumber)->exists());

        return $projectNumber;
    }
}
