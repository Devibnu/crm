@extends('admin.layouts.app')

@section('title', 'Edit Transaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'cart'])
            </div>
            <div>
                <h1>Edit Transaction</h1>
                <p>Perbarui detail transaksi agar data deal customer tetap akurat.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.transactions.update', $transaction) }}">
                @csrf
                @method('PUT')

                @include('admin.customers.transactions_crud._form', [
                    'transaction' => $transaction,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.transactions') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Transaction</button>
                </div>
            </form>
        </article>
    </section>
@endsection
