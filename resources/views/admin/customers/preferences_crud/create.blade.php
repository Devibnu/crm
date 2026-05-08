@extends('admin.layouts.app')

@section('title', 'Add Preference - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1>Add Preference</h1>
                <p>Tambahkan preferensi customer agar komunikasi dan segmentasi lebih tepat.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.preferences.store', $selectedCustomer) }}">
                @csrf

                @include('admin.customers.preferences_crud._form', [
                    'customers' => $customers,
                    'preferredChannelOptions' => $preferredChannelOptions,
                    'selectedCustomer' => $selectedCustomer,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Preference</button>
                </div>
            </form>
        </article>
    </section>
@endsection
