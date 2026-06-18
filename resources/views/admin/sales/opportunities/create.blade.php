@extends('admin.layouts.app')

@section('title', 'Add Opportunity - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'opportunity'])
            </div>
            <div>
                <h1>Add Opportunity</h1>
                <p>Kelola peluang bisnis dan proses discovery.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.sales.opportunities.store') }}">
                @csrf

                @include('admin.sales.opportunities._form', [
                    'leads' => $leads,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'statusLabels' => $statusLabels,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Opportunity</button>
                </div>
            </form>
        </article>
    </section>
@endsection
