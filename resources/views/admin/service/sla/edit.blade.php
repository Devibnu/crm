@extends('admin.layouts.app')

@section('title', 'Edit SLA Policy - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>Edit SLA Policy</h1>
                <div class="customer-profile-hero-meta" aria-label="SLA policy context">
                    <span>{{ $policy->name }}</span>
                    <span>{{ ucfirst($policy->priority) }}</span>
                    <span>{{ $policy->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
            <span class="status-badge status-{{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span>
        </header>

        <form method="POST" action="{{ route('admin.service.sla.update', $policy) }}" class="lead-workspace-form customer-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.service.sla._form')

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.service.sla.show', $policy) }}" class="btn btn-muted">Cancel</a>
                <button type="submit" class="btn btn-primary">Update SLA Policy</button>
            </div>
        </form>
    </section>
@endsection
