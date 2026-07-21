@extends('admin.layouts.app')

@section('title', 'Edit Interaction - Krakatau CRM')

@section('content')
    @php
        $selectedCustomerId = old('customer_id', $interaction->customer_id);
        $selectedCustomerName = $customers->firstWhere('id', (int) $selectedCustomerId)?->name ?: 'No customer selected';
        $selectedType = old('type', $interaction->type);
    @endphp

    <section class="lead-form-page customer-crud-form-page customer-interaction-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">INTERACTION HISTORY</span>
                <h1>Edit Interaction</h1>
                <p>{{ $selectedCustomerName }}</p>
                <div class="customer-form-hero-meta">
                    <span>{{ ucwords(str_replace('_', ' ', $selectedType)) }}</span>
                </div>
                <p>Update communication history for this customer.</p>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.customers.interactions.update', $interaction) }}" class="lead-workspace-form customer-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.customers.interactions._form', [
                'interaction' => $interaction,
                'customers' => $customers,
                'typeOptions' => $typeOptions,
            ])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.interactions') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Interaction</button>
            </div>
        </form>
    </section>
@endsection
