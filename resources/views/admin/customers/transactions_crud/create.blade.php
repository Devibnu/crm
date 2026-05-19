@extends('admin.layouts.app')

@section('title', 'Add Transaction - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Transaction - Krakatau CRM" data-doc-title-id="Tambah Transaksi - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'cart'])
            </div>
            <div>
                <h1 data-lang-en="Add Transaction" data-lang-id="Tambah Transaksi">Add Transaction</h1>
                <p data-lang-en="Add customer transactions to monitor deal pipeline and revenue." data-lang-id="Tambahkan transaksi customer untuk memantau pipeline deal dan revenue.">Tambahkan transaksi customer untuk memantau pipeline deal dan revenue.</p>
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
                    <a href="{{ route('admin.customers.transactions') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Transaction" data-lang-id="Simpan Transaksi">Save Transaction</button>
                </div>
            </form>
        </article>
    </section>
@endsection
