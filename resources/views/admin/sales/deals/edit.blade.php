@extends('admin.layouts.app')

@section('title', 'Edit Quotation - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Quotation - Krakatau CRM" data-doc-title-id="Edit Quotation - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-deals-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'deal'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Offer Desk" data-lang-id="Offer Desk">Offer Desk</span>
                <h1 data-lang-en="Edit Quotation" data-lang-id="Edit Quotation">Edit Quotation</h1>
                <p data-lang-en="Update quotation details, negotiation status, and customer or opportunity context." data-lang-id="Perbarui detail penawaran, status negosiasi, dan konteks customer atau opportunity.">Perbarui detail penawaran, status negosiasi, dan konteks customer atau opportunity.</p>
            </div>
        </article>

        <article class="card customer-form-card sales-deals-form-shell">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $quotation->quote_number }}</h2>
                    <p>{{ $quotation->title }}</p>
                </div>
                <span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.sales.deals.update', $quotation) }}">
                @csrf
                @method('PUT')

                @include('admin.sales.deals._form', [
                    'quotation' => $quotation,
                    'opportunities' => $opportunities,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Quotation" data-lang-id="Ubah Quotation">Update Quotation</button>
                </div>
            </form>
        </article>
    </section>
@endsection
