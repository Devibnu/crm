@extends('admin.layouts.app')

@section('title', 'Add Opportunity - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Opportunity - Krakatau CRM" data-doc-title-id="Tambah Opportunity - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-opportunities-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'opportunity'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Pipeline Studio" data-lang-id="Pipeline Studio">Pipeline Studio</span>
                <h1 data-lang-en="Add Opportunity" data-lang-id="Tambah Opportunity">Add Opportunity</h1>
                <p data-lang-en="Create a new business opportunity and discovery context." data-lang-id="Kelola peluang bisnis dan proses discovery.">Create a new business opportunity and discovery context.</p>
            </div>
        </article>

        <article class="card customer-form-card sales-opportunities-form-shell">
            <div class="sales-section-head sales-form-card-head">
                <div>
                    <h2 data-lang-en="New Opportunity" data-lang-id="Opportunity Baru">New Opportunity</h2>
                    <p data-lang-en="Capture lead context, commercial value, and the expected path to close." data-lang-id="Catat konteks lead, nilai komersial, dan target jalur closing.">Capture lead context, commercial value, and the expected path to close.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.sales.opportunities.store') }}">
                @csrf

                @include('admin.sales.opportunities._form', [
                    'leads' => $leads,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Opportunity" data-lang-id="Simpan Opportunity">Save Opportunity</button>
                </div>
            </form>
        </article>
    </section>
@endsection
