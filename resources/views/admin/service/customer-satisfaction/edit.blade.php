@extends('admin.layouts.app')

@section('title', 'Edit Customer Satisfaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'star'])
            </div>
            <div>
                <h1>Edit Customer Satisfaction</h1>
                <p>Perbarui rating, sentiment, channel survey, dan catatan follow-up.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $satisfaction->customer?->name ?: 'Customer Feedback' }}</h2>
                    <p>{{ $satisfaction->ticket?->ticket_number ?: 'No ticket linked' }}</p>
                </div>
                <span class="status-badge sentiment-{{ $satisfaction->sentiment }}">{{ ucfirst($satisfaction->sentiment) }}</span>
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
