@extends('admin.layouts.app')

@section('title', 'Add Transaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'cart'])
            </div>
            <div>
                <h1>Add Transaction</h1>
                <p>Tambahkan transaksi customer untuk memantau pipeline deal dan revenue.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.transactions.store', $selectedCustomer) }}">
                @csrf

                @include('admin.customers.transactions_crud._form', [
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'selectedCustomer' => $selectedCustomer,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.transactions') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                </div>
            </form>
        </article>
    </section>
@endsection
