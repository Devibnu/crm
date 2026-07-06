@extends('admin.layouts.app')

@section('title', 'Create Project - Krakatau CRM')

@section('content')
    <section class="lead-form-page project-form-page">
        <header class="lead-list-header lead-form-banner">
            <div>
                <span class="crm-record-kicker">PROJECT MANAGEMENT</span>
                <h1>Create Project</h1>
                <p>Buat project dari Deal Won dan pertahankan link CRM end-to-end.</p>
            </div>
            <a href="{{ route('admin.projects.index') }}" class="btn btn-sm lead-banner-secondary">Back</a>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.projects.store') }}" class="lead-workspace-form">
            @csrf

            @include('admin.projects._form')

            <div class="lead-form-actions">
                <a href="{{ route('admin.projects.index') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Save Project</button>
            </div>
        </form>
    </section>
@endsection
