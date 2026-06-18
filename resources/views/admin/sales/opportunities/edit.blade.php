@extends('admin.layouts.app')

@section('title', 'Edit Opportunity - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'opportunity'])
            </div>
            <div>
                <h1>Edit Opportunity</h1>
                <p>Kelola peluang bisnis dan proses discovery.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.sales.opportunities.update', $opportunity) }}">
                @csrf
                @method('PUT')

                @include('admin.sales.opportunities._form', [
                    'opportunity' => $opportunity,
                    'leads' => $leads,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'statusLabels' => $statusLabels,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Opportunity</button>
                </div>
            </form>
        </article>
    </section>
@endsection
