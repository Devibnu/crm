@extends('admin.layouts.app')

@section('title', 'Add Customer - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page">
        @include('admin.customers._success-toast')

        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PROFILE 360</span>
                <h1>Create Customer</h1>
                <p>Create a new customer profile and relationship information.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.customers.store') }}" class="lead-workspace-form customer-workspace-form">
            @csrf

            @include('admin.customers._form')

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </section>
@endsection
