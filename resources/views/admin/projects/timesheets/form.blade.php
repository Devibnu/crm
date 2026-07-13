@extends('admin.layouts.app')

@section('title', ($formMode === 'create' ? 'Create Timesheet' : 'Edit Timesheet').' - Krakatau CRM')

@section('content')
    @php
        $isEdit = $formMode === 'edit';
        $action = $isEdit ? route('admin.projects.timesheets.update', $timesheet) : route('admin.projects.timesheets.store');
        $workDate = old('work_date', $timesheet->work_date instanceof \Illuminate\Support\Carbon ? $timesheet->work_date->format('Y-m-d') : $timesheet->work_date);
    @endphp

    <section class="crm-record-page project-record-page project-timesheet-form-page">
        <header class="lead-detail-banner project-detail-banner">
            <div class="crm-record-heading">
                <span class="crm-record-kicker">PROJECT TIMESHEET</span>
                <div class="crm-record-title-row">
                    <h1>{{ $isEdit ? 'Edit Timesheet' : 'Create Timesheet' }}</h1>
                    <span class="status-badge status-{{ str_replace('_', '-', old('status', $timesheet->status ?: 'draft')) }}">
                        {{ $statusOptions[old('status', $timesheet->status ?: 'draft')] ?? 'Draft' }}
                    </span>
                </div>
                <p>Duration is calculated automatically from start and end time.</p>
            </div>
            <div class="lead-detail-action-stack">
                <div class="crm-record-actions">
                    <a href="{{ route('admin.projects.timesheets.index') }}" class="btn btn-sm lead-banner-secondary">All Timesheets</a>
                </div>
            </div>
        </header>

        <section class="lead-list-card project-timesheet-editor">
            <form method="POST" action="{{ $action }}" class="lead-workspace-form project-inline-form">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="customer-form-grid">
                    <label class="field">
                        <span>Project</span>
                        <select name="project_id" required>
                            <option value="">Select project</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $timesheet->project_id) === (string) $project->id)>{{ $project->project_number }} - {{ $project->title }}</option>
                            @endforeach
                        </select>
                        @error('project_id')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Employee</span>
                        <select name="user_id" required>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('user_id', $timesheet->user_id) === (string) $employee->id)>{{ $employee->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Milestone</span>
                        <select name="milestone_id">
                            <option value="">No milestone</option>
                            @foreach ($milestones as $milestone)
                                <option value="{{ $milestone->id }}" @selected((string) old('milestone_id', $timesheet->milestone_id) === (string) $milestone->id)>{{ $milestone->title }} - {{ $milestone->project?->project_number }}</option>
                            @endforeach
                        </select>
                        @error('milestone_id')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Task</span>
                        <select name="task_id">
                            <option value="">No task</option>
                            @foreach ($tasks as $task)
                                <option value="{{ $task->id }}" @selected((string) old('task_id', $timesheet->task_id) === (string) $task->id)>{{ $task->title }} - {{ $task->project?->project_number }}</option>
                            @endforeach
                        </select>
                        @error('task_id')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Work Date</span>
                        <input type="date" name="work_date" value="{{ $workDate }}" required>
                        @error('work_date')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Status</span>
                        <select name="status" required>
                            @foreach ($statusOptions as $status => $label)
                                <option value="{{ $status }}" @selected(old('status', $timesheet->status ?: 'draft') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Start Time</span>
                        <input type="time" name="start_time" value="{{ old('start_time', substr((string) $timesheet->start_time, 0, 5)) }}" required>
                        @error('start_time')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>End Time</span>
                        <input type="time" name="end_time" value="{{ old('end_time', substr((string) $timesheet->end_time, 0, 5)) }}" required>
                        @error('end_time')<small class="error">{{ $message }}</small>@enderror
                    </label>
                </div>

                <label class="project-timesheet-check">
                    <input type="checkbox" name="billable" value="1" @checked(old('billable', $timesheet->billable ?? true))>
                    <span>Billable time</span>
                </label>

                <label class="field">
                    <span>Description</span>
                    <textarea name="description" rows="4" placeholder="Describe the work completed">{{ old('description', $timesheet->description) }}</textarea>
                    @error('description')<small class="error">{{ $message }}</small>@enderror
                </label>

                <div class="project-form-actions">
                    <button class="btn lead-banner-cta" type="submit">{{ $isEdit ? 'Save Timesheet' : 'Create Timesheet' }}</button>
                    <a href="{{ $isEdit ? route('admin.projects.timesheets.show', $timesheet) : route('admin.projects.timesheets.index') }}" class="btn btn-muted">Cancel</a>
                </div>
            </form>
        </section>
    </section>
@endsection
