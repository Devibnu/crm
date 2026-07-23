@extends('admin.layouts.app')

@section('title', 'Add SLA Policy - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>Add SLA Policy</h1>
                <p>Buat aturan response dan resolution target untuk Ticket Management.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.service.sla.store') }}" class="lead-workspace-form customer-workspace-form">
            @csrf

            @include('admin.service.sla._form')

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.service.sla.index') }}" class="btn btn-muted">Cancel</a>
                <button type="submit" class="btn btn-primary">Save SLA Policy</button>
            </div>
        </form>
    </section>
@endsection
