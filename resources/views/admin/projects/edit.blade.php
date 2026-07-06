@extends('admin.layouts.app')

@section('title', 'Edit Project - Krakatau CRM')

@section('content')
    <section class="lead-form-page project-form-page">
        <header class="lead-form-banner">
            <div>
                <span>Project Management</span>
                <h1>Edit Project</h1>
                <p>Update project overview. Progress remains calculated from milestones.</p>
            </div>
            <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-sm lead-banner-secondary">Back</a>
        </header>

        <form method="POST" action="{{ route('admin.projects.update', $project) }}" class="lead-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.projects._form')

            <div class="lead-form-actions">
                <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn lead-banner-cta">Update Project</button>
            </div>
        </form>
    </section>
@endsection
