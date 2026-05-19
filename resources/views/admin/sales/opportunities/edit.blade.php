@extends('admin.layouts.app')

@section('title', 'Edit Opportunity - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Opportunity - Krakatau CRM" data-doc-title-id="Edit Opportunity - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-opportunities-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'opportunity'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Pipeline Studio" data-lang-id="Pipeline Studio">Pipeline Studio</span>
                <h1 data-lang-en="Edit Opportunity" data-lang-id="Edit Opportunity">Edit Opportunity</h1>
                <p data-lang-en="Update the business opportunity and discovery context." data-lang-id="Kelola peluang bisnis dan proses discovery.">Update the business opportunity and discovery context.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card sales-opportunities-form-shell">
            <div class="sales-section-head sales-form-card-head">
                <div>
                    <h2>{{ $opportunity->title }}</h2>
                    <p>{{ $opportunity->company_name ?: __('Pipeline opportunity') }}</p>
                </div>
                <span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.sales.opportunities.update', $opportunity) }}">
                @csrf
                @method('PUT')

                @include('admin.sales.opportunities._form', [
                    'opportunity' => $opportunity,
                    'leads' => $leads,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Opportunity" data-lang-id="Ubah Opportunity">Update Opportunity</button>
                </div>
            </form>
        </article>
    </section>
@endsection
