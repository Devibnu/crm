@extends('admin.layouts.app')

@section('title', 'Add Customer Satisfaction - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Customer Satisfaction - Krakatau CRM" data-doc-title-id="Tambah Kepuasan Pelanggan - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'star'])
            </div>
            <div>
                <h1 data-lang-en="Add Customer Satisfaction" data-lang-id="Tambah Kepuasan Pelanggan">Add Customer Satisfaction</h1>
                <p data-lang-en="Record rating, sentiment, feedback, and customer follow-up needs." data-lang-id="Catat rating, sentiment, feedback, dan kebutuhan follow-up pelanggan.">Catat rating, sentiment, feedback, dan kebutuhan follow-up pelanggan.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Feedback" data-lang-id="Feedback Baru">New Feedback</h2>
                    <p data-lang-en="Link the feedback to a ticket or customer if available." data-lang-id="Hubungkan feedback ke ticket atau customer jika tersedia.">Hubungkan feedback ke ticket atau customer jika tersedia.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.customer-satisfaction.store') }}">
                @csrf

                @include('admin.service.customer-satisfaction._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.customer-satisfaction.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Feedback" data-lang-id="Simpan Feedback">Save Feedback</button>
                </div>
            </form>
        </article>
    </section>
@endsection
