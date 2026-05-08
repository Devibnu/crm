@extends('admin.layouts.app')

@section('title', 'Edit Lead - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <h1>Edit Lead</h1>
                <p>Perbarui data lead agar proses sales tetap akurat.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.sales.leads.update', $lead) }}">
                @csrf
                @method('PUT')

                @include('admin.sales.leads._form', [
                    'lead' => $lead,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'priorityOptions' => $priorityOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.leads.show', $lead) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Lead</button>
                </div>
            </form>
        </article>
    </section>
@endsection
