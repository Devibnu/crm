@extends('admin.layouts.app')

@section('title', 'Add Customer - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        @include('admin.customers._success-toast')

        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <h1>Add Customer</h1>
                <p>Tambahkan data customer/contact baru untuk Customer Profile 360.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.store') }}">
                @csrf

                @include('admin.customers._form')

                <div class="form-actions">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                </div>
            </form>
        </article>
    </section>
@endsection
