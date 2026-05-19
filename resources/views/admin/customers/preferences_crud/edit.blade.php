@extends('admin.layouts.app')

@section('title', 'Edit Preference - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Preference - Krakatau CRM" data-doc-title-id="Edit Preferensi - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1 data-lang-en="Edit Preference" data-lang-id="Edit Preferensi">Edit Preference</h1>
                <p data-lang-en="Update customer preferences to keep communication accurate." data-lang-id="Perbarui preferensi customer untuk menjaga akurasi komunikasi.">Perbarui preferensi customer untuk menjaga akurasi komunikasi.</p>
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
                    <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Preference" data-lang-id="Ubah Preferensi">Update Preference</button>
                </div>
            </form>
        </article>
    </section>
@endsection
