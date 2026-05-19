@extends('admin.layouts.app')

@section('title', 'Knowledge Base - Krakatau CRM')

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

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Articles</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua artikel knowledge base</small>
            </article>
            <article class="card sales-summary-card">
                <span>Published</span>
                <strong>{{ number_format($summary['published']) }}</strong>
                <small>Artikel yang sudah dipublikasikan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Draft</span>
                <strong>{{ number_format($summary['draft']) }}</strong>
                <small>Artikel belum dipublikasikan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Public Articles</span>
                <strong>{{ number_format($summary['public']) }}</strong>
                <small>Dapat dilihat customer</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Article List</h2>
                    <p>Search title, content, atau tags. Filter berdasarkan category, visibility, dan published status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.service.knowledge-base.create') }}" class="btn btn-primary">Add Article</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.knowledge-base.index') }}" class="kb-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Title, content, tags">
                </label>
                <label class="field">
                    <span>Category</span>
                    <select name="category">
                        <option value="">Semua category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Visibility</span>
                    <select name="visibility">
                        <option value="">Semua visibility</option>
                        @foreach ($visibilityOptions as $visibility)
                            <option value="{{ $visibility }}" @selected($selectedVisibility === $visibility)>{{ ucfirst($visibility) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Published</span>
                    <select name="published">
                        <option value="">Semua status</option>
                        @foreach ($publishedOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedPublished === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedCategory || $selectedVisibility || $selectedPublished)
                        <a href="{{ route('admin.service.knowledge-base.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Visibility</th>
                            <th>Published Status</th>
                            <th>Views</th>
                            <th>Author</th>
                            <th>Published Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($articles as $article)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.service.knowledge-base.show', $article) }}" class="sales-title-link">{{ $article->title }}</a>
                                    <small>{{ $article->tags ?: '-' }}</small>
                                </td>
                                <td><span class="status-badge kb-category">{{ $article->category ?: '-' }}</span></td>
                                <td><span class="status-badge visibility-{{ $article->visibility }}">{{ ucfirst($article->visibility) }}</span></td>
                                <td><span class="status-badge status-{{ $article->is_published ? 'active' : 'inactive' }}">{{ $article->is_published ? 'Published' : 'Draft' }}</span></td>
                                <td>{{ number_format($article->views_count) }}</td>
                                <td>{{ $article->author_name ?: '-' }}</td>
                                <td>{{ $article->published_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.service.knowledge-base.show', $article) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.service.knowledge-base.edit', $article) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.service.knowledge-base.destroy', $article) }}" onsubmit="return confirm('Delete article ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada artikel</strong>
                                        <span>Tambahkan artikel pertama untuk membangun self-service customer.</span>
                                        <a href="{{ route('admin.service.knowledge-base.create') }}" class="btn btn-primary">Add Article</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($articles->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $articles->firstItem() }}-{{ $articles->lastItem() }} dari {{ $articles->total() }} artikel
                    </div>
                    <div class="pagination-links">
                        @if ($articles->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $articles->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($articles->getUrlRange(max(1, $articles->currentPage() - 2), min($articles->lastPage(), $articles->currentPage() + 2)) as $page => $url)
                            @if ($page === $articles->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($articles->hasMorePages())
                            <a href="{{ $articles->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
