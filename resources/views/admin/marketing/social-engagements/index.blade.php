@extends('admin.layouts.app')

@section('title', 'Social Media Engagement - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'social'])
            </div>
            <div>
                <h1>Social Media Engagement</h1>
                <p>Monitoring engagement social media campaign dan performa content marketing.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Posts</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua content social</small>
            </article>
            <article class="card sales-summary-card">
                <span>Published Posts</span>
                <strong>{{ number_format($summary['published']) }}</strong>
                <small>Sudah dipublikasikan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Impressions</span>
                <strong>{{ number_format($summary['impressions']) }}</strong>
                <small>Akumulasi impressions</small>
            </article>
            <article class="card sales-summary-card">
                <span>Average Engagement Rate</span>
                <strong>{{ number_format($summary['average_engagement_rate'], 2) }}%</strong>
                <small>Rata-rata tersimpan</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Social Posts</h2>
                    <p>Search post title atau content, lalu filter platform dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.social-engagements.create') }}" class="btn btn-primary">Add Post</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.social-engagements.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Post title or content" aria-label="Search social posts">
                </label>
                <label class="field">
                    <span>Platform</span>
                    <select name="platform" aria-label="Filter platform">
                        <option value="">All platforms</option>
                        @foreach ($platformOptions as $platform)
                            <option value="{{ $platform }}" @selected($selectedPlatform === $platform)>{{ ucfirst($platform) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status" aria-label="Filter status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($search || $selectedPlatform || $selectedStatus)
                        <a href="{{ route('admin.marketing.social-engagements.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Post Title</th>
                            <th>Campaign</th>
                            <th>Status</th>
                            <th>Likes</th>
                            <th>Comments</th>
                            <th>Shares</th>
                            <th>Impressions</th>
                            <th>Engagement Rate</th>
                            <th>Posted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $post)
                            <tr>
                                <td><span class="status-badge platform-{{ $post->platform }}">{{ ucfirst($post->platform) }}</span></td>
                                <td><a href="{{ route('admin.marketing.social-engagements.show', $post) }}" class="sales-title-link">{{ $post->post_title }}</a></td>
                                <td>{{ $post->marketingCampaign?->name ?: '-' }}</td>
                                <td><span class="status-badge status-{{ $post->status }}">{{ ucfirst($post->status) }}</span></td>
                                <td>{{ number_format($post->likes_count) }}</td>
                                <td>{{ number_format($post->comments_count) }}</td>
                                <td>{{ number_format($post->shares_count) }}</td>
                                <td>{{ number_format($post->impressions_count) }}</td>
                                <td>{{ number_format((float) $post->engagement_rate, 2) }}%</td>
                                <td>{{ $post->posted_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.social-engagements.show', $post) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.marketing.social-engagements.edit', $post) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.social-engagements.destroy', $post) }}" onsubmit="return confirm('Delete social post ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada social post</strong>
                                        <span>Tambahkan post pertama untuk tracking engagement social media.</span>
                                        <a href="{{ route('admin.marketing.social-engagements.create') }}" class="btn btn-primary">Add Post</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($posts->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $posts->firstItem() }}-{{ $posts->lastItem() }} dari {{ $posts->total() }} post
                    </div>
                    <div class="pagination-links">
                        @if ($posts->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $posts->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($posts->getUrlRange(max(1, $posts->currentPage() - 2), min($posts->lastPage(), $posts->currentPage() + 2)) as $page => $url)
                            @if ($page === $posts->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($posts->hasMorePages())
                            <a href="{{ $posts->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
