@extends('admin.layouts.app')

@section('title', 'Add Interaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'mail'])
            </div>
            <div>
                <h1>Add Interaction</h1>
                <p>Tambahkan catatan interaksi customer untuk histori komunikasi yang lebih rapi.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.interactions.store', $selectedCustomer) }}">
                @csrf

                @include('admin.customers.interactions._form', [
                    'customers' => $customers,
                    'typeOptions' => $typeOptions,
                    'selectedCustomer' => $selectedCustomer,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.interactions') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Interaction</button>
                </div>
            </form>
        </article>
    </section>
@endsection
