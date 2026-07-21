@extends('admin.layouts.app')

@section('title', 'Add Interaction - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page customer-interaction-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER INTERACTION</span>
                <h1>Add Interaction</h1>
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
                <p>Record a customer communication, activity, or follow-up.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.customers.interactions.store', $selectedCustomer) }}" class="lead-workspace-form customer-workspace-form">
            @csrf

            @include('admin.customers.interactions._form', [
                'customers' => $customers,
                'typeOptions' => $typeOptions,
                'selectedCustomer' => $selectedCustomer,
            ])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.interactions', ['q' => $selectedCustomer->name]) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Save Interaction</button>
            </div>
        </form>
    </section>
@endsection
