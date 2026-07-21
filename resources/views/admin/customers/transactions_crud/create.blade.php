@extends('admin.layouts.app')

@section('title', 'Add Transaction - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page customer-transaction-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER TRANSACTIONS</span>
                <h1>New Transaction</h1>
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
                <p>Create a customer transaction, deal value, and closing information.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.customers.transactions.store', $selectedCustomer) }}" class="lead-workspace-form customer-workspace-form">
            @csrf

            @include('admin.customers.transactions_crud._form', [
                'customers' => $customers,
                'statusOptions' => $statusOptions,
                'selectedCustomer' => $selectedCustomer,
            ])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.transactions') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Create Transaction</button>
            </div>
        </form>
    </section>
@endsection
