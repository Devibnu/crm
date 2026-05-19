@extends('admin.layouts.app')

@section('title', 'Add Lead - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Lead - Krakatau CRM" data-doc-title-id="Tambah Lead - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-leads-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Pipeline Intake" data-lang-id="Pipeline Intake">Pipeline Intake</span>
                <h1 data-lang-en="Add Lead" data-lang-id="Tambah Lead">Add Lead</h1>
                <p data-lang-en="Add a new lead for assignment and qualification." data-lang-id="Tambahkan lead baru untuk proses assignment dan kualifikasi.">Tambahkan lead baru untuk proses assignment dan kualifikasi.</p>
            </div>
        </article>

        <article class="card customer-form-card sales-leads-form-shell">
            <div class="sales-section-head sales-form-card-head">
                <div>
                    <h2 data-lang-en="New Lead" data-lang-id="Lead Baru">New Lead</h2>
                    <p data-lang-en="Capture the incoming prospect clearly so qualification and handoff stay clean." data-lang-id="Catat prospek masuk dengan jelas agar kualifikasi dan handoff tetap rapi.">Capture the incoming prospect clearly so qualification and handoff stay clean.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.sales.leads.store') }}">
                @csrf

                @include('admin.sales.leads._form', [
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'priorityOptions' => $priorityOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.leads') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Lead" data-lang-id="Simpan Lead">Save Lead</button>
                </div>
            </form>
        </article>
    </section>
@endsection
