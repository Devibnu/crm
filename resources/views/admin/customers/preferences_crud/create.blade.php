@extends('admin.layouts.app')

@section('title', 'Add Preference - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Preference - Krakatau CRM" data-doc-title-id="Tambah Preferensi - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1 data-lang-en="Add Preference" data-lang-id="Tambah Preferensi">Add Preference</h1>
                <p data-lang-en="Add customer preferences so communication and segmentation become more precise." data-lang-id="Tambahkan preferensi customer agar komunikasi dan segmentasi lebih tepat.">Tambahkan preferensi customer agar komunikasi dan segmentasi lebih tepat.</p>
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
                    <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Preference" data-lang-id="Simpan Preferensi">Save Preference</button>
                </div>
            </form>
        </article>
    </section>
@endsection
