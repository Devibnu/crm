@extends('admin.layouts.app')

@section('title', 'Add Case Resolution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">
                    @include('admin.partials.sidebar-icon', ['icon' => 'case'])
                </div>
                <div>
                    <span class="crm-record-kicker">CASE RESOLUTION</span>
                    <h1>New Resolution</h1>
                    <div class="customer-profile-hero-meta" aria-label="Resolution workspace context">
                        <span>Document how a service ticket was solved</span>
                        <span>Root cause</span>
                        <span>Outcome</span>
                        <span>Knowledge candidate</span>
                    </div>
                </div>
            </div>
        </header>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>Resolution Workspace</h2>
                    <p>Select the ticket and capture the resolution details for future service learning.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.case-resolutions.store') }}">
                @csrf

                @include('admin.service.case-resolutions._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.case-resolutions.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Resolution</button>
                </div>
            </form>
        </article>
    </section>
@endsection
