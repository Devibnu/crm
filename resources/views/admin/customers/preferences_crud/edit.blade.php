@extends('admin.layouts.app')

@section('title', 'Edit Preference - Krakatau CRM')

@section('content')
    @php
        $selectedCustomerId = old('customer_id', $preference->customer_id);
        $selectedCustomerName = $customers->firstWhere('id', (int) $selectedCustomerId)?->name ?: 'No customer selected';
        $selectedChannel = old('preferred_channel', $preference->preferred_channel);
    @endphp

    <section class="lead-form-page customer-crud-form-page customer-preference-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PREFERENCES</span>
                <h1>Edit Preference</h1>
                <p>{{ $selectedCustomerName }}</p>
                <div class="customer-form-hero-meta">
                    <span>{{ ucfirst($selectedChannel) }}</span>
                </div>
                <p>Update communication preference, product interest, consent, and segmentation context.</p>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.customers.preferences.update', $preference) }}" class="lead-workspace-form customer-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.customers.preferences_crud._form', [
                'preference' => $preference,
                'customers' => $customers,
                'preferredChannelOptions' => $preferredChannelOptions,
            ])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Preference</button>
            </div>
        </form>
    </section>
@endsection
