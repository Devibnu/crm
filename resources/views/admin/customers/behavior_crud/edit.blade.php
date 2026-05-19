@extends('admin.layouts.app')

@section('title', 'Edit Behavior - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Behavior - Krakatau CRM" data-doc-title-id="Edit Perilaku - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1 data-lang-en="Edit Behavior" data-lang-id="Edit Perilaku">Edit Behavior</h1>
                <p data-lang-en="Update behavior data so the customer profile remains more accurate." data-lang-id="Perbarui data behavior agar profil customer lebih akurat.">Perbarui data behavior agar profil customer lebih akurat.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.behavior.update', $behavior) }}">
                @csrf
                @method('PUT')

                @include('admin.customers.behavior_crud._form', [
                    'behavior' => $behavior,
                    'customers' => $customers,
                    'lifecycleStageOptions' => $lifecycleStageOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Behavior" data-lang-id="Ubah Perilaku">Update Behavior</button>
                </div>
            </form>
        </article>
    </section>
@endsection
