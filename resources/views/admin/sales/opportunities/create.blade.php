@extends('admin.layouts.app')

@section('title', 'Add Opportunity - Krakatau CRM')

@section('content')
    <section class="lead-form-page opportunity-form-page">
        <header class="lead-list-header lead-form-banner">
            <div>
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Add Opportunity</h1>
                <p>Tambahkan peluang bisnis baru ke dalam sales pipeline.</p>
            </div>
            <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-sm lead-banner-secondary">Back</a>
        </header>

        <form method="POST" action="{{ route('admin.sales.opportunities.store') }}" class="lead-workspace-form opportunity-workspace-form">
            @csrf

            <section class="opportunity-form-panel">
                @include('admin.sales.opportunities._form', [
                    'leads' => $leads,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'statusLabels' => $statusLabels,
                    'sourceLead' => $sourceLead,
                ])
            </section>

            <div class="lead-form-actions">
                <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Save Opportunity</button>
            </div>
        </form>
    </section>
@endsection
