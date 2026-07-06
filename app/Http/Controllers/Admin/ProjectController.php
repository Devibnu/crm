<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Projects\ProjectActivityLogger;
use App\Services\Projects\ProjectProgressEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('admin.projects.dashboard', [
            'totalProjects' => Project::query()->count(),
            'activeProjects' => Project::query()->where('status', 'active')->count(),
            'completedProjects' => Project::query()->where('status', 'completed')->count(),
            'averageProgress' => (int) round((float) Project::query()->avg('progress')),
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

        $projects = Project::query()
            ->with(['customer:id,name', 'opportunity:id,title', 'quotation:id,quote_number'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('project_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('quotation', fn ($quotation) => $quotation->where('quote_number', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'search' => $search,
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
        return view('admin.projects.show', [
            'project' => $project->load([
                'customer:id,name',
                'lead:id,name',
                'opportunity:id,title,status,won_at,lost_at',
                'quotation:id,quote_number,title,status,amount',
                'creator:id,name',
                'projectManager:id,name,email',
                'members.user:id,name,email',
                'milestones',
                'activityLogs.actor:id,name',
            ]),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'memberRoles' => $this->memberRoleOptions(),
            'milestoneStatusOptions' => $this->milestoneStatusOptions(),
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys($this->milestoneStatusOptions()))],
            'due_date' => ['nullable', 'date'],
        ]);
        $validated['sort_order'] = (int) $project->milestones()->max('sort_order') + 1;
        $validated['completed_at'] = $validated['status'] === 'completed' ? now() : null;

        $milestone = $project->milestones()->create($validated);
        $this->progressEngine->refresh($project);

        $event = $milestone->status === 'completed' ? 'milestone_completed' : 'milestone_created';
        $description = $milestone->status === 'completed'
            ? 'Milestone Completed: '.$milestone->title
            : 'Milestone Created: '.$milestone->title;

        $this->activityLogger->log($project, $event, $description, $milestone);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Milestone berhasil ditambahkan.');
    }

    public function updateMilestone(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys($this->milestoneStatusOptions()))],
            'due_date' => ['nullable', 'date'],
        ]);

        $oldStatus = $milestone->status;
        $validated['completed_at'] = $validated['status'] === 'completed'
            ? ($milestone->completed_at ?: now())
            : null;

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

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Milestone berhasil diperbarui.');
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
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
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
