@extends('admin.layouts.app')

@section('title', 'Add Preference - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page customer-preference-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PREFERENCES</span>
                <h1>New Preference</h1>
                <p>{{ $selectedCustomer->name }}</p>
                <div class="customer-form-hero-meta">
                    @if ($selectedCustomer->company_name)
                        <span>{{ $selectedCustomer->company_name }}</span>
                    @endif
                    @if ($selectedCustomer->email)
                        <span>{{ $selectedCustomer->email }}</span>
                    @elseif ($selectedCustomer->phone)
                        <span>{{ $selectedCustomer->phone }}</span>
                    @endif
                </div>
                <p>Create communication preferences, product interest, consent, and segmentation context.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.customers.preferences.store', $selectedCustomer) }}" class="lead-workspace-form customer-workspace-form">
            @csrf

            @include('admin.customers.preferences_crud._form', [
                'customers' => $customers,
                'preferredChannelOptions' => $preferredChannelOptions,
                'selectedCustomer' => $selectedCustomer,
            ])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Create Preference</button>
            </div>
        </form>
    </section>
@endsection
