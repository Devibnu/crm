@extends('admin.layouts.app')

@section('title', 'Add Quotation - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Quotation - Krakatau CRM" data-doc-title-id="Tambah Quotation - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-deals-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'deal'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Offer Desk" data-lang-id="Offer Desk">Offer Desk</span>
                <h1 data-lang-en="Add Quotation" data-lang-id="Tambah Quotation">Add Quotation</h1>
                <p data-lang-en="Create a new quotation with a clear customer, opportunity, amount, and validity date." data-lang-id="Buat penawaran baru dengan customer, opportunity, amount, dan tanggal valid yang jelas.">Buat penawaran baru dengan customer, opportunity, amount, dan tanggal valid yang jelas.</p>
            </div>
        </article>

        <article class="card customer-form-card sales-deals-form-shell">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Quotation" data-lang-id="Quotation Baru">New Quotation</h2>
                    <p data-lang-en="Fill in the main details first, then link it to a customer or opportunity if available." data-lang-id="Isi detail utama terlebih dahulu, lalu hubungkan ke customer atau opportunity jika tersedia.">Isi detail utama terlebih dahulu, lalu hubungkan ke customer atau opportunity jika tersedia.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.sales.deals.store') }}">
                @csrf

                @include('admin.sales.deals._form', [
                    'opportunities' => $opportunities,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'prefillOpportunityId' => $prefillOpportunityId,
                    'prefillCustomerId' => $prefillCustomerId,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Quotation" data-lang-id="Simpan Quotation">Save Quotation</button>
                </div>
            </form>
        </article>
    </section>
@endsection
