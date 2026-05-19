@extends('admin.layouts.app')

@section('title', 'Add Behavior - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Behavior - Krakatau CRM" data-doc-title-id="Tambah Perilaku - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1 data-lang-en="Add Behavior" data-lang-id="Tambah Perilaku">Add Behavior</h1>
                <p data-lang-en="Add customer behavior data for lifecycle and engagement insights." data-lang-id="Tambahkan data behavior customer untuk insight lifecycle dan engagement.">Tambahkan data behavior customer untuk insight lifecycle dan engagement.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.behavior.store', $selectedCustomer) }}">
                @csrf

                @include('admin.customers.behavior_crud._form', [
                    'customers' => $customers,
                    'lifecycleStageOptions' => $lifecycleStageOptions,
                    'selectedCustomer' => $selectedCustomer,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Behavior" data-lang-id="Simpan Perilaku">Save Behavior</button>
                </div>
            </form>
        </article>
    </section>
@endsection
