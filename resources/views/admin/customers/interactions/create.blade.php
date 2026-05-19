@extends('admin.layouts.app')

@section('title', 'Add Interaction - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Interaction - Krakatau CRM" data-doc-title-id="Tambah Interaksi - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'mail'])
            </div>
            <div>
                <h1 data-lang-en="Add Interaction" data-lang-id="Tambah Interaksi">Add Interaction</h1>
                <p data-lang-en="Add customer interaction notes for a cleaner communication history." data-lang-id="Tambahkan catatan interaksi customer untuk histori komunikasi yang lebih rapi.">Tambahkan catatan interaksi customer untuk histori komunikasi yang lebih rapi.</p>
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
                    <a href="{{ route('admin.customers.interactions') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Interaction" data-lang-id="Simpan Interaksi">Save Interaction</button>
                </div>
            </form>
        </article>
    </section>
@endsection
