@extends('admin.layouts.app')

@section('title', 'Add SLA Policy - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'timer'])
            </div>
            <div>
                <h1>Add SLA Policy</h1>
                <p>Buat aturan response time dan resolution time untuk tiket layanan pelanggan.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New SLA Policy</h2>
                    <p>Tentukan priority dan target waktu layanan yang akan dipakai oleh tim support.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.sla.store') }}">
                @csrf

                @include('admin.service.sla._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.sla.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save SLA Policy</button>
                </div>
            </form>
        </article>
    </section>
@endsection
