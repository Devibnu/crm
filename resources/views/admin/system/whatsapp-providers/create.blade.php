@extends('admin.layouts.app')

@section('title', 'Add WhatsApp Provider - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>Add WhatsApp Provider</h1>
                <p>Tambahkan koneksi provider WhatsApp untuk fondasi integrasi CRM.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Provider</h2>
                    <p>Konfigurasi provider tanpa mengaktifkan pengiriman API terlebih dahulu.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.system.whatsapp-providers.store') }}">
                @csrf

                @include('admin.system.whatsapp-providers._form')

                <div class="form-actions">
                    <a href="{{ route('admin.system.whatsapp-providers.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Provider</button>
                </div>
            </form>
        </article>
    </section>
@endsection
