@extends('admin.layouts.app')

@section('title', 'Add Customer - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Customer - Krakatau CRM" data-doc-title-id="Tambah Customer - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <h1 data-lang-en="Add Customer" data-lang-id="Tambah Customer">Add Customer</h1>
                <p data-lang-en="Add a new customer/contact for Customer Profile 360." data-lang-id="Tambahkan data customer/contact baru untuk Customer Profile 360.">Tambahkan data customer/contact baru untuk Customer Profile 360.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.store') }}">
                @csrf

                @include('admin.customers._form')

                <div class="form-actions">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Customer" data-lang-id="Simpan Customer">Save Customer</button>
                </div>
            </form>
        </article>
    </section>
@endsection
