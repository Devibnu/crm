@extends('admin.layouts.app')

@section('title', 'Add Sales Activity - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Sales Activity - Krakatau CRM" data-doc-title-id="Tambah Aktivitas Sales - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-activities-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Field Motion" data-lang-id="Field Motion">Field Motion</span>
                <h1 data-lang-en="Add Sales Activity" data-lang-id="Tambah Aktivitas Sales">Add Sales Activity</h1>
                <p data-lang-en="Add a new sales activity for a lead, opportunity, or customer." data-lang-id="Tambahkan aktivitas sales baru untuk lead, opportunity, atau customer.">Tambahkan aktivitas sales baru untuk lead, opportunity, atau customer.</p>
            </div>
        </article>

        <article class="card customer-form-card sales-activities-form-shell">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Activity" data-lang-id="Aktivitas Baru">New Activity</h2>
                    <p data-lang-en="Link the activity to related data, then fill in the activity and assignment information." data-lang-id="Hubungkan aktivitas ke data terkait, lalu isi informasi activity dan assignment.">Hubungkan aktivitas ke data terkait, lalu isi informasi activity dan assignment.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.sales.activities.store') }}">
                @csrf

                @include('admin.sales.activities._form')

                <div class="form-actions">
                    <a href="{{ route('admin.sales.activities.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Activity" data-lang-id="Simpan Aktivitas">Save Activity</button>
                </div>
            </form>
        </article>
    </section>
@endsection
