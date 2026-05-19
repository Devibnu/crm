@extends('admin.layouts.app')

@section('title', 'Edit Interaction - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Interaction - Krakatau CRM" data-doc-title-id="Edit Interaksi - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'mail'])
            </div>
            <div>
                <h1 data-lang-en="Edit Interaction" data-lang-id="Edit Interaksi">Edit Interaction</h1>
                <p data-lang-en="Update interaction details so customer history remains accurate." data-lang-id="Perbarui detail interaction agar histori customer tetap akurat.">Perbarui detail interaction agar histori customer tetap akurat.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.interactions.update', $interaction) }}">
                @csrf
                @method('PUT')

                @include('admin.customers.interactions._form', [
                    'interaction' => $interaction,
                    'customers' => $customers,
                    'typeOptions' => $typeOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.interactions') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Interaction" data-lang-id="Ubah Interaksi">Update Interaction</button>
                </div>
            </form>
        </article>
    </section>
@endsection
