@extends('admin.layouts.app')

@section('title', 'Edit Customer Satisfaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">
                    @include('admin.partials.sidebar-icon', ['icon' => 'star'])
                </div>
                <div>
                    <span class="crm-record-kicker">CUSTOMER SATISFACTION</span>
                    <h1>Edit Customer Satisfaction</h1>
                    <div class="customer-profile-hero-meta" aria-label="Feedback summary">
                        <span>{{ $satisfaction->customer?->name ?: 'No customer linked' }}</span>
                        <span>{{ $satisfaction->ticket?->ticket_number ?: 'No ticket linked' }}</span>
                        <span>{{ $satisfaction->rating }}/5 rating</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                <span class="status-badge sentiment-{{ $satisfaction->sentiment }}">{{ ucfirst($satisfaction->sentiment) }}</span>
            </div>
        </header>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $satisfaction->customer?->name ?: 'Customer Feedback' }}</h2>
                    <p>{{ $satisfaction->ticket?->ticket_number ?: 'No ticket linked' }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.customer-satisfaction.update', $satisfaction) }}">
                @csrf
                @method('PUT')

                @include('admin.service.customer-satisfaction._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.customer-satisfaction.show', $satisfaction) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Feedback</button>
                </div>
            </form>
        </article>
    </section>
@endsection
