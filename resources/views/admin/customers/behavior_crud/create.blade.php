@extends('admin.layouts.app')

@section('title', 'Add Behavior - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page customer-behavior-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER BEHAVIOR</span>
                <h1>New Behavior</h1>
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
                <p>Create lifecycle, engagement, activity, and product interest signals for this customer.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.customers.behavior.store', $selectedCustomer) }}" class="lead-workspace-form customer-workspace-form">
            @csrf

            @include('admin.customers.behavior_crud._form', [
                'customers' => $customers,
                'lifecycleStageOptions' => $lifecycleStageOptions,
                'selectedCustomer' => $selectedCustomer,
            ])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Create Behavior</button>
            </div>
        </form>
    </section>
@endsection
