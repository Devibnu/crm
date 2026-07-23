@extends('admin.layouts.app')

@section('title', 'Add Business Calendar - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>Add Business Calendar</h1>
                <p>Create support operating hours, timezone, and working-day configuration.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.service.business-calendars.store') }}" class="lead-workspace-form customer-workspace-form">
            @csrf

            @include('admin.service.business-calendars._form')

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.service.business-calendars.index') }}" class="btn btn-muted">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Business Calendar</button>
            </div>
        </form>
    </section>
@endsection
