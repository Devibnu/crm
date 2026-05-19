@extends('admin.layouts.app')

@section('title', 'Edit Lead - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Lead - Krakatau CRM" data-doc-title-id="Edit Lead - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-leads-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Pipeline Intake" data-lang-id="Pipeline Intake">Pipeline Intake</span>
                <h1 data-lang-en="Edit Lead" data-lang-id="Edit Lead">Edit Lead</h1>
                <p data-lang-en="Update lead data so the sales process remains accurate." data-lang-id="Perbarui data lead agar proses sales tetap akurat.">Perbarui data lead agar proses sales tetap akurat.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card sales-leads-form-shell">
            <div class="sales-section-head sales-form-card-head">
                <div>
                    <h2>{{ $lead->name }}</h2>
                    <p>{{ $lead->company_name ?: 'Lead qualification record' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $lead->status }}">{{ ucfirst($lead->status) }}</span>
                    <span class="status-badge priority-{{ $lead->priority }}">{{ ucfirst($lead->priority) }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.sales.leads.update', $lead) }}">
                @csrf
                @method('PUT')

                @include('admin.sales.leads._form', [
                    'lead' => $lead,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'priorityOptions' => $priorityOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.leads.show', $lead) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Lead" data-lang-id="Ubah Lead">Update Lead</button>
                </div>
            </form>
        </article>
    </section>
@endsection
