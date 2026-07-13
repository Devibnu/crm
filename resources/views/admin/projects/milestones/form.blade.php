@extends('admin.layouts.app')

@section('title', ($formMode === 'create' ? 'Create Milestone' : 'Edit Milestone').' - Krakatau CRM')

@section('content')
    @php
        $isEdit = $formMode === 'edit';
        $action = $isEdit
            ? route('admin.projects.milestones.update', [$project, $milestone])
            : route('admin.projects.milestones.store', $project);
    @endphp

    <section class="crm-record-page project-record-page project-milestone-form-page">
        <header class="lead-detail-banner project-detail-banner">
            <div class="crm-record-heading">
                <span class="crm-record-kicker">PROJECT MILESTONE</span>
                <div class="crm-record-title-row">
                    <h1>{{ $isEdit ? 'Edit Milestone' : 'Create Milestone' }}</h1>
                    <span class="status-badge status-{{ str_replace('_', '-', old('status', $milestone->status ?: 'planning')) }}">
                        {{ $statusOptions[old('status', $milestone->status ?: 'planning')] ?? 'Planning' }}
                    </span>
                </div>
                <p>{{ $project->project_number }} - {{ $project->title }}</p>
            </div>
            <div class="lead-detail-action-stack">
                <div class="crm-record-actions">
                    <a href="{{ route('admin.projects.milestones.index') }}" class="btn btn-sm lead-banner-secondary">All Milestones</a>
                    <a href="{{ route('admin.projects.show', ['project' => $project, 'tab' => 'milestones']) }}" class="btn btn-sm btn-muted">Back to Project</a>
                </div>
            </div>
        </header>

        <section class="lead-list-card project-milestone-editor">
            <form method="POST" action="{{ $action }}" class="lead-workspace-form project-inline-form">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif
                <input type="hidden" name="redirect_to" value="milestone">

                <div class="customer-form-grid">
                    <label class="field">
                        <span>Milestone Name</span>
                        <input type="text" name="title" value="{{ old('title', $milestone->title) }}" placeholder="Design signoff, UAT, Go Live" required>
                        @error('title')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Status</span>
                        <select name="status" required>
                            @foreach ($statusOptions as $status => $label)
                                <option value="{{ $status }}" @selected(old('status', $milestone->status ?: 'planning') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Start Date</span>
                        <input type="date" name="start_date" value="{{ old('start_date', $milestone->start_date?->format('Y-m-d')) }}">
                        @error('start_date')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Due Date</span>
                        <input type="date" name="due_date" value="{{ old('due_date', $milestone->due_date?->format('Y-m-d')) }}">
                        @error('due_date')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Color</span>
                        <select name="color">
                            @foreach ($colorOptions as $color => $label)
                                <option value="{{ $color }}" @selected(old('color', $milestone->color ?: 'blue') === $color)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('color')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <label class="field">
                        <span>Icon</span>
                        <select name="icon">
                            @foreach ($iconOptions as $icon => $label)
                                <option value="{{ $icon }}" @selected(old('icon', $milestone->icon ?: 'calendar') === $icon)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('icon')<small class="error">{{ $message }}</small>@enderror
                    </label>
                </div>

                <label class="field">
                    <span>Description</span>
                    <textarea name="description" rows="4" placeholder="What must be true when this milestone is complete?">{{ old('description', $milestone->description) }}</textarea>
                    @error('description')<small class="error">{{ $message }}</small>@enderror
                </label>

                <div class="project-form-actions">
                    <button class="btn lead-banner-cta" type="submit">{{ $isEdit ? 'Save Milestone' : 'Create Milestone' }}</button>
                    <a href="{{ $isEdit ? route('admin.projects.milestones.show', [$project, $milestone]) : route('admin.projects.show', ['project' => $project, 'tab' => 'milestones']) }}" class="btn btn-muted">Cancel</a>
                </div>
            </form>
        </section>
    </section>
@endsection
