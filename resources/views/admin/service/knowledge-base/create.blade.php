@extends('admin.layouts.app')

@section('title', 'Add Knowledge Base Article - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'book'])
            </div>
            <div>
                <h1>Add Knowledge Base Article</h1>
                <p>Buat artikel bantuan, FAQ, troubleshooting, atau panduan onboarding.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Article</h2>
                    <p>Isi informasi artikel, konten, lalu atur visibility dan published status.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.knowledge-base.store') }}">
                @csrf

                @include('admin.service.knowledge-base._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.knowledge-base.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Article</button>
                </div>
            </form>
        </article>
    </section>
@endsection
