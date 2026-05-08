@extends('admin.layouts.app')

@section('title', $article->title.' - Knowledge Base - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'book'])
            </div>
            <div>
                <h1>Knowledge Base</h1>
                <p>Pusat artikel bantuan, FAQ, dan self-service customer.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card kb-article-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $article->title }}</h2>
                    <p>{{ $article->slug }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $article->is_published ? 'active' : 'inactive' }}">{{ $article->is_published ? 'Published' : 'Draft' }}</span>
                    <a href="{{ route('admin.service.knowledge-base.edit', $article) }}" class="btn btn-primary">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Category</span>
                    <strong>{{ $article->category ?: '-' }}</strong>
                </div>
                <div>
                    <span>Visibility</span>
                    <strong>{{ ucfirst($article->visibility) }}</strong>
                </div>
                <div>
                    <span>Views</span>
                    <strong>{{ number_format($article->views_count) }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Category</strong><span><span class="status-badge kb-category">{{ $article->category ?: '-' }}</span></span></div>
                <div><strong>Visibility</strong><span><span class="status-badge visibility-{{ $article->visibility }}">{{ ucfirst($article->visibility) }}</span></span></div>
                <div><strong>Published Status</strong><span>{{ $article->is_published ? 'Published' : 'Draft' }}</span></div>
                <div><strong>Author</strong><span>{{ $article->author_name ?: '-' }}</span></div>
                <div><strong>Tags</strong><span>{{ $article->tags ?: '-' }}</span></div>
                <div><strong>Published Date</strong><span>{{ $article->published_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Views Count</strong><span>{{ number_format($article->views_count) }}</span></div>
            </div>

            <div class="customer-notes kb-content">
                <h3>Content</h3>
                <div>{!! nl2br(e($article->content)) !!}</div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.service.knowledge-base.index') }}" class="btn btn-muted">Back</a>
                <form method="POST" action="{{ route('admin.service.knowledge-base.destroy', $article) }}" onsubmit="return confirm('Delete article ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
