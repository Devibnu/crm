@extends('admin.layouts.app')

@section('title', 'Edit Preference - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1>Edit Preference</h1>
                <p>Perbarui preferensi customer untuk menjaga akurasi komunikasi.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.preferences.update', $preference) }}">
                @csrf
                @method('PUT')

                @include('admin.customers.preferences_crud._form', [
                    'preference' => $preference,
                    'customers' => $customers,
                    'preferredChannelOptions' => $preferredChannelOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Preference</button>
                </div>
            </form>
        </article>
    </section>
@endsection
