@extends('admin.layouts.app')

@section('title', 'Edit Behavior - Krakatau CRM')

@section('content')
    @php
        $selectedCustomerId = old('customer_id', $behavior->customer_id);
        $selectedCustomerName = $customers->firstWhere('id', (int) $selectedCustomerId)?->name ?: 'No customer selected';
        $selectedLifecycleStage = old('lifecycle_stage', $behavior->lifecycle_stage);
    @endphp

    <section class="lead-form-page customer-crud-form-page customer-behavior-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER BEHAVIOR</span>
                <h1>Edit Behavior</h1>
                <p>{{ $selectedCustomerName }}</p>
                <div class="customer-form-hero-meta">
                    <span>{{ ucfirst($selectedLifecycleStage) }}</span>
                </div>
                <p>Update lifecycle, engagement, activity, and product interest signals for this customer.</p>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.customers.behavior.update', $behavior) }}" class="lead-workspace-form customer-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.customers.behavior_crud._form', [
                'behavior' => $behavior,
                'customers' => $customers,
                'lifecycleStageOptions' => $lifecycleStageOptions,
            ])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Behavior</button>
            </div>
        </form>
    </section>
@endsection
