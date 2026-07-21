@extends('admin.layouts.app')

@section('title', 'Edit Customer - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page">
        @include('admin.customers._success-toast')

        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PROFILE 360</span>
                <h1>Edit Customer</h1>
                <p>{{ $customer->name }}</p>
                <div class="customer-form-hero-meta">
                    <span>{{ $customer->company_name ?: 'No company' }}</span>
                </div>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="lead-workspace-form customer-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.customers._form', ['customer' => $customer])

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Customer</button>
            </div>
        </form>
    </section>
@endsection
