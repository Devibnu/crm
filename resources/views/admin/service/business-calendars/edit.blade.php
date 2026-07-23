@extends('admin.layouts.app')

@section('title', 'Edit Business Calendar - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>Edit Business Calendar</h1>
                <div class="customer-profile-hero-meta" aria-label="Business calendar context">
                    <span>{{ $calendar->name }}</span>
                    <span>{{ $calendar->timezone }}</span>
                    <span>{{ $calendar->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
            <div class="customer-profile-actions">
                @if ($calendar->is_default)
                    <span class="status-badge status-active">Default</span>
                @endif
                <span class="status-badge status-{{ $calendar->is_active ? 'active' : 'inactive' }}">{{ $calendar->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.service.business-calendars.update', $calendar) }}" class="lead-workspace-form customer-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.service.business-calendars._form')

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.service.business-calendars.show', $calendar) }}" class="btn btn-muted">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Business Calendar</button>
            </div>
        </form>
    </section>
@endsection
