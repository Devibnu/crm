@extends('admin.layouts.app')

@section('title', 'Add Quotation - Krakatau CRM')

@section('content')
    <section class="lead-form-page quotation-form-page">
        <header class="lead-list-header lead-form-banner">
            <div>
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Add Quotation</h1>
                <p>Buat penawaran baru dengan customer, opportunity, amount, dan tanggal valid yang jelas.</p>
            </div>
            <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-sm lead-banner-secondary">Back</a>
        </header>

        <form method="POST" action="{{ route('admin.sales.deals.store') }}" class="lead-workspace-form quotation-workspace-form">
            @csrf

            @include('admin.sales.deals._form', [
                'opportunities' => $opportunities,
                'customers' => $customers,
                'statusOptions' => $statusOptions,
                'prefillOpportunityId' => $prefillOpportunityId,
                'prefillCustomerId' => $prefillCustomerId,
            ])

            <div class="lead-form-actions">
                <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Save Quotation</button>
            </div>
        </form>
    </section>
@endsection
