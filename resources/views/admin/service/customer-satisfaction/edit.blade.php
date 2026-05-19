@extends('admin.layouts.app')

@section('title', 'Edit Customer Satisfaction - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Customer Satisfaction - Krakatau CRM" data-doc-title-id="Edit Kepuasan Pelanggan - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'star'])
            </div>
            <div>
                <h1 data-lang-en="Edit Customer Satisfaction" data-lang-id="Edit Kepuasan Pelanggan">Edit Customer Satisfaction</h1>
                <p data-lang-en="Update rating, sentiment, survey channel, and follow-up notes." data-lang-id="Perbarui rating, sentiment, channel survey, dan catatan follow-up.">Perbarui rating, sentiment, channel survey, dan catatan follow-up.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $satisfaction->customer?->name ?: '' }}@unless($satisfaction->customer?->name)<span data-lang-en="Customer Feedback" data-lang-id="Feedback Customer">Customer Feedback</span>@endunless</h2>
                    <p>{{ $satisfaction->ticket?->ticket_number ?: '' }}@unless($satisfaction->ticket?->ticket_number)<span data-lang-en="No ticket linked" data-lang-id="Belum terhubung ke tiket">No ticket linked</span>@endunless</p>
                </div>
                <span class="status-badge sentiment-{{ $satisfaction->sentiment }}">{{ ucfirst($satisfaction->sentiment) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.service.customer-satisfaction.update', $satisfaction) }}">
                @csrf
                @method('PUT')

                @include('admin.service.customer-satisfaction._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.customer-satisfaction.show', $satisfaction) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Feedback" data-lang-id="Ubah Feedback">Update Feedback</button>
                </div>
            </form>
        </article>
    </section>
@endsection
