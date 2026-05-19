@extends('admin.layouts.app')

@section('title', 'Edit Lead Scoring Rule - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Edit Lead Scoring Rule - Krakatau CRM" data-doc-title-id="Ubah Aturan Lead Scoring - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'scoring'])
            </div>
            <div>
                <h1 data-lang-en="Edit Rule" data-lang-id="Ubah Aturan">Edit Rule</h1>
                <p data-lang-en="Update the scoring, routing, and JSON conditions for the lead rule." data-lang-id="Perbarui scoring, routing, dan JSON kondisi untuk aturan lead.">Perbarui scoring, routing, dan condition JSON untuk rule lead.</p>
            </div>
        </article>

        <form method="POST" action="{{ route('admin.marketing.lead-scoring.update', $rule) }}" class="card customer-form-card">
            @csrf
            @method('PUT')
            @include('admin.marketing.lead-scoring._form')

            <div class="form-actions">
                <a href="{{ route('admin.marketing.lead-scoring.show', $rule) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                <button type="submit" class="btn btn-primary" data-lang-en="Update Rule" data-lang-id="Perbarui Aturan">Update Rule</button>
            </div>
        </form>
    </section>
@endsection
