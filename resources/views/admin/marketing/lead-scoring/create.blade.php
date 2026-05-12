@extends('admin.layouts.app')

@section('title', 'Add Lead Scoring Rule - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'scoring'])
            </div>
            <div>
                <h1>Add Rule</h1>
                <p>Buat rule scoring dan routing lead untuk distribusi sales yang lebih konsisten.</p>
            </div>
        </article>

        <form method="POST" action="{{ route('admin.marketing.lead-scoring.store') }}" class="card customer-form-card">
            @csrf
            @include('admin.marketing.lead-scoring._form')

            <div class="form-actions">
                <a href="{{ route('admin.marketing.lead-scoring.index') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Save Rule</button>
            </div>
        </form>
    </section>
@endsection
