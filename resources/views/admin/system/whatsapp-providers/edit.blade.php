@extends('admin.layouts.app')

@section('title', 'Edit WhatsApp Provider - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>Edit WhatsApp Provider</h1>
                <p>Perbarui konfigurasi provider WhatsApp CRM.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $whatsappProvider->name }}</h2>
                    <p>{{ strtoupper($whatsappProvider->provider) }}</p>
                </div>
                <span class="status-badge status-{{ $whatsappProvider->status }}">{{ ucfirst($whatsappProvider->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.system.whatsapp-providers.update', $whatsappProvider) }}">
                @csrf
                @method('PUT')

                @include('admin.system.whatsapp-providers._form')

                <div class="form-actions">
                    <a href="{{ route('admin.system.whatsapp-providers.show', $whatsappProvider) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Provider</button>
                </div>
            </form>
        </article>
    </section>
@endsection
