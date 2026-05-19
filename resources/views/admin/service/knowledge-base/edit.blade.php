@extends('admin.layouts.app')

@section('title', 'Edit Knowledge Base Article - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'book'])
            </div>
            <div>
                <h1>Edit Knowledge Base Article</h1>
                <p>Perbarui artikel bantuan, content, tags, visibility, dan published status.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $article->title }}</h2>
                    <p>{{ $article->slug }}</p>
                </div>
                <span class="status-badge status-{{ $article->is_published ? 'active' : 'inactive' }}">{{ $article->is_published ? 'Published' : 'Draft' }}</span>
            </div>

            <form method="POST" action="{{ route('admin.service.knowledge-base.update', $article) }}">
                @csrf
                @method('PUT')

                @include('admin.service.knowledge-base._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.knowledge-base.show', $article) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Article</button>
                </div>
            </form>
        </article>
    </section>
@endsection
