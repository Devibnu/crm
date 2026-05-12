@extends('admin.layouts.app')

@section('title', 'Edit Lead Scoring Rule - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'scoring'])
            </div>
            <div>
                <h1>Edit Rule</h1>
                <p>Perbarui scoring, routing, dan condition JSON untuk rule lead.</p>
            </div>
        </article>

        <form method="POST" action="{{ route('admin.marketing.lead-scoring.update', $rule) }}" class="card customer-form-card">
            @csrf
            @method('PUT')
            @include('admin.marketing.lead-scoring._form')

            <div class="form-actions">
                <a href="{{ route('admin.marketing.lead-scoring.show', $rule) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Rule</button>
            </div>
        </form>
    </section>
@endsection
