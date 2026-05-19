@extends('admin.layouts.app')

@section('title', 'Add Lead Scoring Rule - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Add Lead Scoring Rule - Krakatau CRM" data-doc-title-id="Tambah Aturan Lead Scoring - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'scoring'])
            </div>
            <div>
                <h1 data-lang-en="Add Rule" data-lang-id="Tambah Aturan">Add Rule</h1>
                <p data-lang-en="Create a lead scoring and routing rule for more consistent sales distribution." data-lang-id="Buat aturan scoring dan routing lead untuk distribusi sales yang lebih konsisten.">Buat rule scoring dan routing lead untuk distribusi sales yang lebih konsisten.</p>
            </div>
        </article>

        <form method="POST" action="{{ route('admin.marketing.lead-scoring.store') }}" class="card customer-form-card">
            @csrf
            @include('admin.marketing.lead-scoring._form')

            <div class="form-actions">
                <a href="{{ route('admin.marketing.lead-scoring.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                <button type="submit" class="btn btn-primary" data-lang-en="Save Rule" data-lang-id="Simpan Aturan">Save Rule</button>
            </div>
        </form>
    </section>
@endsection
