@extends('admin.layouts.app')

@section('title', 'Add Quotation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'deal'])
            </div>
            <div>
                <h1>Add Quotation</h1>
                <p>Buat penawaran baru dengan customer, opportunity, amount, dan tanggal valid yang jelas.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Quotation</h2>
                    <p>Isi detail utama terlebih dahulu, lalu hubungkan ke customer atau opportunity jika tersedia.</p>
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
                    <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Quotation</button>
                </div>
            </form>
        </article>
    </section>
@endsection
