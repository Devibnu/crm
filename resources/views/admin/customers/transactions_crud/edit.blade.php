@extends('admin.layouts.app')

@section('title', 'Edit Transaction - Krakatau CRM')

@section('content')
    @php
        $selectedCustomerId = old('customer_id', $transaction->customer_id);
        $selectedCustomerName = $customers->firstWhere('id', (int) $selectedCustomerId)?->name ?: 'No customer selected';
        $selectedStatus = old('status', $transaction->status);
    @endphp

    <section class="lead-form-page customer-crud-form-page customer-transaction-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER TRANSACTIONS</span>
                <h1>Edit Transaction</h1>
                <p>{{ $selectedCustomerName }}</p>
                <div class="customer-form-hero-meta">
                    <span>{{ ucfirst($selectedStatus) }}</span>
                </div>
                <p>Update transaction value, status, and closing information.</p>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.customers.transactions.update', $transaction) }}" class="lead-workspace-form customer-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.customers.transactions_crud._form', [
                'transaction' => $transaction,
                'customers' => $customers,
                'statusOptions' => $statusOptions,
            ])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.transactions') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Transaction</button>
            </div>
        </form>
    </section>
@endsection
