@extends('admin.layouts.app')

@section('title', 'Edit Customer - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Customer - Krakatau CRM" data-doc-title-id="Edit Customer - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1 data-lang-en="Edit Customer" data-lang-id="Edit Customer">Edit Customer</h1>
                <p data-lang-en="Update the customer data completely." data-lang-id="Perbarui data customer secara lengkap.">Perbarui data customer secara lengkap.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}">
                @csrf
                @method('PUT')

                @include('admin.customers._form', ['customer' => $customer])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Customer" data-lang-id="Ubah Customer">Update Customer</button>
                </div>
            </form>
        </article>
    </section>
@endsection
