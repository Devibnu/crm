@extends('admin.layouts.app')

@section('title', 'Edit Customer - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1>Edit Customer</h1>
                <p>Perbarui data customer secara lengkap.</p>
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
                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </form>
        </article>
    </section>
@endsection
