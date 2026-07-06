@extends('admin.layouts.app')

@section('title', $project->title.' - Project - Krakatau CRM')

@section('content')
    @php
        $dealStatus = $project->quotation?->status === 'accepted' || $project->opportunity?->status === 'won' ? 'Won' : 'Pending';
        $completedMilestones = $project->milestones->where('status', 'completed')->count();
        $totalMilestones = $project->milestones->count();
    @endphp

    <section class="crm-record-page project-record-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-detail-banner">
            <div class="crm-record-heading">
                <a href="{{ route('admin.projects.index') }}" class="lead-detail-back">Back to Project Management</a>
                <span class="crm-record-kicker">Project Management</span>
                <div class="crm-record-title-row">
                    <h1>{{ $project->title }}</h1>
                    <span class="status-badge status-{{ str_replace('_', '-', $project->status) }}">{{ str($project->status)->headline() }}</span>
                </div>
                <p>{{ $project->project_number }} · Rp {{ number_format((float) $project->budget, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm lead-banner-secondary">Edit Project</a>
        </header>

        <div class="crm-metadata-row">
            <div><span>Project Number</span><strong>{{ $project->project_number }}</strong></div>
            <div><span>Budget</span><strong>Rp {{ number_format((float) $project->budget, 2, ',', '.') }}</strong></div>
            <div><span>Progress</span><strong>{{ $project->progress }}%</strong></div>
            <div><span>Start Date</span><strong>{{ $project->start_date?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Due Date</span><strong>{{ $project->due_date?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Project Manager</span><strong>{{ $project->projectManager?->name ?: '-' }}</strong></div>
        </div>

        <div class="crm-record-workspace">
            <main class="crm-workspace-main">
                <section class="crm-tab-content">
                    <div class="crm-content-heading">
                        <div><h2>Overview</h2><p>Project delivery foundation linked from Deal Won.</p></div>
                    </div>
                    <div class="crm-related-list">
                        <div><span>Project Name</span><strong>{{ $project->title }}</strong></div>
                        <div><span>Customer</span><strong>{{ $project->customer?->name ?: '-' }}</strong></div>
                        <div><span>Lead</span><strong>{{ $project->lead?->name ?: '-' }}</strong></div>
                        <div><span>Opportunity</span><strong>{{ $project->opportunity?->title ?: '-' }}</strong></div>
                        <div><span>Quotation</span><strong>{{ $project->quotation?->quote_number ?: '-' }}</strong></div>
                        <div><span>Deal</span><strong>{{ $dealStatus }}</strong></div>
                        <div><span>Status</span><strong>{{ str($project->status)->headline() }}</strong></div>
                        <div><span>Progress Engine</span><strong>{{ $completedMilestones }} / {{ $totalMilestones }} milestones completed</strong></div>
                    </div>
                    <div class="crm-notes-content">{{ $project->description ?: 'No description available.' }}</div>
                </section>

                <section id="members" class="crm-tab-content">
                    <div class="crm-content-heading">
                        <div><h2>Members</h2><p>Project team and delivery responsibility.</p></div>
                    </div>
                    <form method="POST" action="{{ route('admin.projects.members.store', $project) }}" class="lead-workspace-form">
                        @csrf
                        <div class="customer-form-grid">
                            <label class="field">
                                <span>User</span>
                                <select name="user_id" required>
                                    <option value="">Pilih user</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                                @error('user_id')<small class="error">{{ $message }}</small>@enderror
                            </label>
                            <label class="field">
                                <span>Role</span>
                                <select name="role" required>
                                    @foreach ($memberRoles as $role => $label)
                                        <option value="{{ $role }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('role')<small class="error">{{ $message }}</small>@enderror
                            </label>
                        </div>
                        <button class="btn btn-sm lead-banner-cta" type="submit">Add Member</button>
                    </form>

                    <div class="crm-related-list">
                        @forelse ($project->members as $member)
                            <div>
                                <span>{{ $memberRoles[$member->role] ?? str($member->role)->headline() }}</span>
                                <strong>{{ $member->user?->name ?: '-' }}</strong>
                                <form method="POST" action="{{ route('admin.projects.members.destroy', [$project, $member]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-muted" type="submit">Remove</button>
                                </form>
                            </div>
                        @empty
                            <div><span>Team</span><strong>No members yet.</strong></div>
                        @endforelse
                    </div>
                </section>

                <section id="milestones" class="crm-tab-content">
                    <div class="crm-content-heading">
                        <div><h2>Milestones</h2><p>Progress is calculated from completed milestones.</p></div>
                    </div>
                    <form method="POST" action="{{ route('admin.projects.milestones.store', $project) }}" class="lead-workspace-form">
                        @csrf
                        <div class="customer-form-grid">
                            <label class="field">
                                <span>Milestone</span>
                                <input type="text" name="title" placeholder="Requirement, Design, Development..." required>
                                @error('title')<small class="error">{{ $message }}</small>@enderror
                            </label>
                            <label class="field">
                                <span>Status</span>
                                <select name="status" required>
                                    @foreach ($milestoneStatusOptions as $status => $label)
                                        <option value="{{ $status }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<small class="error">{{ $message }}</small>@enderror
                            </label>
                            <label class="field">
                                <span>Due Date</span>
                                <input type="date" name="due_date">
                                @error('due_date')<small class="error">{{ $message }}</small>@enderror
                            </label>
                        </div>
                        <label class="field">
                            <span>Description</span>
                            <textarea name="description" rows="2"></textarea>
                        </label>
                        <button class="btn btn-sm lead-banner-cta" type="submit">Add Milestone</button>
                    </form>

                    <div class="crm-related-list">
                        @forelse ($project->milestones as $milestone)
                            <div>
                                <span>{{ $milestone->due_date?->format('d M Y') ?: 'No due date' }}</span>
                                <strong>{{ $milestone->title }}</strong>
                                <small>{{ $milestoneStatusOptions[$milestone->status] ?? str($milestone->status)->headline() }}</small>
                                <form method="POST" action="{{ route('admin.projects.milestones.update', [$project, $milestone]) }}" class="lead-workspace-form">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="title" value="{{ $milestone->title }}">
                                    <input type="hidden" name="description" value="{{ $milestone->description }}">
                                    <input type="hidden" name="due_date" value="{{ $milestone->due_date?->format('Y-m-d') }}">
                                    <select name="status">
                                        @foreach ($milestoneStatusOptions as $status => $label)
                                            <option value="{{ $status }}" @selected($milestone->status === $status)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-muted" type="submit">Update Status</button>
                                </form>
                            </div>
                        @empty
                            <div><span>Milestones</span><strong>No milestones yet.</strong></div>
                        @endforelse
                    </div>
                </section>

                <section id="timeline" class="crm-tab-content">
                    <div class="crm-content-heading">
                        <div><h2>Timeline</h2><p>Automatic project activity history.</p></div>
                    </div>
                    <div class="crm-related-list">
                        @forelse ($project->activityLogs as $activity)
                            <div>
                                <span>{{ $activity->created_at->format('d M Y H:i') }}</span>
                                <strong>{{ $activity->description }}</strong>
                                <small>{{ $activity->actor?->name ?: 'System' }} · {{ str($activity->event)->headline() }}</small>
                            </div>
                        @empty
                            <div><span>Timeline</span><strong>No activity yet.</strong></div>
                        @endforelse
                    </div>
                </section>

                <section id="files" class="crm-tab-content">
                    <div class="crm-content-heading"><div><h2>Files</h2><p>Reserved for document management sprint.</p></div></div>
                    <div class="crm-notes-content">Files foundation tab is ready. File management will be implemented in a later sprint.</div>
                </section>

                <section id="notes" class="crm-tab-content">
                    <div class="crm-content-heading"><div><h2>Notes</h2><p>Reserved for project notes sprint.</p></div></div>
                    <div class="crm-notes-content">Notes foundation tab is ready. Project notes will be implemented in a later sprint.</div>
                </section>

                <section id="activity" class="crm-tab-content">
                    <div class="crm-content-heading"><div><h2>Activity</h2><p>Same automatic feed used by timeline.</p></div></div>
                    <div class="crm-notes-content">Activity events are captured automatically when project, members, or milestones change.</div>
                </section>
            </main>

            <aside class="crm-workspace-sidebar crm-related-sidebar">
                <section class="crm-workspace-section">
                    <h2>Project Tabs</h2>
                    <div class="crm-related-list">
                        @foreach (['Overview' => '#', 'Members' => '#members', 'Milestones' => '#milestones', 'Timeline' => '#timeline', 'Files' => '#files', 'Notes' => '#notes', 'Activity' => '#activity'] as $label => $href)
                            <div><span>{{ $label }}</span><a href="{{ $href }}">Open</a></div>
                        @endforeach
                    </div>
                </section>

                <section class="crm-workspace-section">
                    <h2>Related Records</h2>
                    <div class="crm-related-list">
                        <div><span>Customer</span>@if ($project->customer)<a href="{{ route('admin.customers.show', $project->customer) }}">{{ $project->customer->name }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Lead</span>@if ($project->lead)<a href="{{ route('admin.sales.leads.show', $project->lead) }}">Open Lead</a>@else<strong>-</strong>@endif</div>
                        <div><span>Opportunity</span>@if ($project->opportunity)<a href="{{ route('admin.sales.opportunities.show', $project->opportunity) }}">Open Opportunity</a>@else<strong>-</strong>@endif</div>
                        <div><span>Quotation</span>@if ($project->quotation)<a href="{{ route('admin.sales.deals.show', $project->quotation) }}">{{ $project->quotation->quote_number }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Deal</span><strong>{{ $dealStatus }}</strong></div>
                        <div><span>Created By</span><strong>{{ $project->creator?->name ?: '-' }}</strong></div>
                    </div>
                </section>
            </aside>
        </div>
    </section>
@endsection
