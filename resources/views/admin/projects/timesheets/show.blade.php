@extends('admin.layouts.app')

@section('title', 'Timesheet Detail - Krakatau CRM')

@section('content')
    <section class="crm-record-page project-record-page project-timesheet-show-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-detail-banner project-detail-banner">
            <div class="crm-record-heading">
                <span class="crm-record-kicker">PROJECT TIMESHEET</span>
                <div class="crm-record-title-row">
                    <h1>{{ $timesheet->user?->name }} - {{ $timesheet->work_date?->format('d M Y') }}</h1>
                    <span class="status-badge status-{{ str_replace('_', '-', $timesheet->status) }}">{{ $timesheet->statusLabel() }}</span>
                </div>
                <p>{{ $timesheet->project?->project_number }} - {{ $timesheet->project?->title }}</p>
            </div>
            <div class="lead-detail-action-stack">
                <div class="crm-record-actions">
                    <a href="{{ route('admin.projects.timesheets.index') }}" class="btn btn-sm lead-banner-secondary">All Timesheets</a>
                    <a href="{{ route('admin.projects.timesheets.edit', $timesheet) }}" class="btn btn-sm btn-muted">Edit</a>
                </div>
            </div>
        </header>

        <div class="project-timesheet-detail-grid">
            <section class="lead-list-card project-timesheet-detail-card">
                <div class="crm-content-heading"><div><h2>Work Log</h2><p>Project, milestone, task, and employee context.</p></div></div>
                <dl class="project-timesheet-detail-list">
                    <div><dt>Project</dt><dd><a href="{{ route('admin.projects.show', $timesheet->project) }}">{{ $timesheet->project?->title }}</a><small>{{ $timesheet->project?->project_number }}</small></dd></div>
                    <div><dt>Milestone</dt><dd>{{ $timesheet->milestone?->title ?: '-' }}</dd></div>
                    <div><dt>Task</dt><dd>{{ $timesheet->task?->title ?: '-' }}</dd></div>
                    <div><dt>Employee</dt><dd>{{ $timesheet->user?->name }}<small>{{ $timesheet->user?->email }}</small></dd></div>
                    <div><dt>Timeline</dt><dd>{{ $timesheet->work_date?->format('d M Y') }}<small>{{ substr((string) $timesheet->start_time, 0, 5) }} - {{ substr((string) $timesheet->end_time, 0, 5) }}</small></dd></div>
                    <div><dt>Duration</dt><dd>{{ $timesheet->durationLabel() }}</dd></div>
                    <div><dt>Billable</dt><dd>{{ $timesheet->billable ? 'Billable' : 'Non billable' }}</dd></div>
                    <div><dt>Description</dt><dd>{{ $timesheet->description ?: 'No description added.' }}</dd></div>
                </dl>
            </section>

            <aside class="lead-list-card project-timesheet-approval-card">
                <div class="crm-content-heading"><div><h2>Approval</h2><p>Manager review state and notes.</p></div></div>
                <dl class="project-timesheet-detail-list">
                    <div><dt>Status</dt><dd>{{ $timesheet->statusLabel() }}</dd></div>
                    <div><dt>Approved By</dt><dd>{{ $timesheet->approver?->name ?: '-' }}</dd></div>
                    <div><dt>Approved At</dt><dd>{{ $timesheet->approved_at?->format('d M Y H:i') ?: '-' }}</dd></div>
                    <div><dt>Approval Note</dt><dd>{{ $timesheet->approval_note ?: '-' }}</dd></div>
                </dl>

                <form method="POST" action="{{ route('admin.projects.timesheets.approve', $timesheet) }}" class="project-timesheet-approval-form">
                    @csrf
                    @method('PUT')
                    <textarea name="approval_note" rows="3" placeholder="Approval note">{{ old('approval_note', $timesheet->approval_note) }}</textarea>
                    <div>
                        <button type="submit" class="btn btn-sm lead-banner-cta">Approve</button>
                        <button type="submit" formaction="{{ route('admin.projects.timesheets.reject', $timesheet) }}" class="btn btn-sm btn-muted">Reject</button>
                    </div>
                </form>
            </aside>
        </div>
    </section>
@endsection
