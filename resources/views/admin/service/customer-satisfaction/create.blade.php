@extends('admin.layouts.app')

@section('title', 'Add Customer Satisfaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'star'])
            </div>
            <div>
                <h1>Add Customer Satisfaction</h1>
                <p>Catat rating, sentiment, feedback, dan kebutuhan follow-up pelanggan.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Feedback</h2>
                    <p>Hubungkan feedback ke ticket atau customer jika tersedia.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.customer-satisfaction.store') }}">
                @csrf

                @include('admin.service.customer-satisfaction._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.customer-satisfaction.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Feedback</button>
                </div>
            </form>
        </article>
    </section>
@endsection
