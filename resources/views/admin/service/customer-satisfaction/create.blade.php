@extends('admin.layouts.app')

@section('title', 'Add Customer Satisfaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">
                    @include('admin.partials.sidebar-icon', ['icon' => 'star'])
                </div>
                <div>
                    <span class="crm-record-kicker">CUSTOMER SATISFACTION</span>
                    <h1>Add Customer Satisfaction</h1>
                    <div class="customer-profile-hero-meta" aria-label="Feedback workspace context">
                        <span>Capture customer feedback</span>
                        <span>Rating and sentiment</span>
                        <span>Follow-up tracking</span>
                    </div>
                </div>
            </div>
        </header>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>Feedback Workspace</h2>
                    <p>Hubungkan feedback ke ticket atau customer jika tersedia, lalu catat rating dan kebutuhan follow-up.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.customer-satisfaction.store') }}">
                @csrf

                @include('admin.service.customer-satisfaction._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.customer-satisfaction.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Create Feedback</button>
                </div>
            </form>
        </article>
    </section>
@endsection
