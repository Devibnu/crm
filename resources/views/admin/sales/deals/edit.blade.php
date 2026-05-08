@extends('admin.layouts.app')

@section('title', 'Edit Quotation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'deal'])
            </div>
            <div>
                <h1>Edit Quotation</h1>
                <p>Perbarui detail penawaran, status negosiasi, dan konteks customer atau opportunity.</p>
            </div>
        </article>

        <article class="card customer-form-card">
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
                    <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Quotation</button>
                </div>
            </form>
        </article>
    </section>
@endsection
