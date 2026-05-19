@extends('admin.layouts.app')

@section('title', 'Social Media Engagement - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Social Media Engagement - Krakatau CRM" data-doc-title-id="Engagement Media Sosial - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'social'])
            </div>
            <div>
                <h1 data-lang-en="Social Media Engagement" data-lang-id="Engagement Media Sosial">Social Media Engagement</h1>
                <p data-lang-en="Monitor social media campaign engagement and content marketing performance." data-lang-id="Monitoring engagement campaign social media dan performa content marketing.">Monitoring engagement social media campaign dan performa content marketing.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Posts" data-lang-id="Total Post">Total Posts</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All social content" data-lang-id="Semua konten sosial">Semua content social</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Published Posts" data-lang-id="Post Dipublikasikan">Published Posts</span>
                <strong>{{ number_format($summary['published']) }}</strong>
                <small data-lang-en="Already published" data-lang-id="Sudah dipublikasikan">Sudah dipublikasikan</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Total Impressions" data-lang-id="Total Impression">Total Impressions</span>
                <strong>{{ number_format($summary['impressions']) }}</strong>
                <small data-lang-en="Accumulated impressions" data-lang-id="Akumulasi impression">Akumulasi impressions</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Average Engagement Rate" data-lang-id="Rata-rata Engagement Rate">Average Engagement Rate</span>
                <strong>{{ number_format($summary['average_engagement_rate'], 2) }}%</strong>
                <small data-lang-en="Stored average" data-lang-id="Rata-rata tersimpan">Rata-rata tersimpan</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Social Posts" data-lang-id="Post Sosial">Social Posts</h2>
                    <p data-lang-en="Search by post title or content, then filter by platform and status." data-lang-id="Cari berdasarkan judul post atau konten, lalu filter berdasarkan platform dan status.">Search post title atau content, lalu filter platform dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.social-engagements.create') }}" class="btn btn-primary" data-lang-en="Add Post" data-lang-id="Tambah Post">Add Post</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.social-engagements.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Post title or content" aria-label="Search social posts" data-placeholder-en="Post title or content" data-placeholder-id="Judul post atau konten" data-title-en="Search social posts" data-title-id="Cari post sosial">
                </label>
                <label class="field">
                    <span data-lang-en="Platform" data-lang-id="Platform">Platform</span>
                    <select name="platform" aria-label="Filter platform">
                        <option value="" data-lang-en="All platforms" data-lang-id="Semua platform">All platforms</option>
                        @foreach ($platformOptions as $platform)
                            <option value="{{ $platform }}" @selected($selectedPlatform === $platform)>{{ ucfirst($platform) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <select name="status" aria-label="Filter status">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Apply Filter" data-lang-id="Terapkan Filter">Apply Filter</button>
                    @if ($search || $selectedPlatform || $selectedStatus)
                        <a href="{{ route('admin.marketing.social-engagements.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Platform" data-lang-id="Platform">Platform</th>
                            <th data-lang-en="Post Title" data-lang-id="Judul Post">Post Title</th>
                            <th data-lang-en="Campaign" data-lang-id="Campaign">Campaign</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Likes" data-lang-id="Likes">Likes</th>
                            <th data-lang-en="Comments" data-lang-id="Komentar">Comments</th>
                            <th data-lang-en="Shares" data-lang-id="Share">Shares</th>
                            <th data-lang-en="Impressions" data-lang-id="Impression">Impressions</th>
                            <th data-lang-en="Engagement Rate" data-lang-id="Engagement Rate">Engagement Rate</th>
                            <th data-lang-en="Posted At" data-lang-id="Diposting Pada">Posted At</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                        <a href="{{ route('admin.marketing.social-engagements.show', $post) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.marketing.social-engagements.edit', $post) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.social-engagements.destroy', $post) }}" data-confirm-en="Delete this social post?" data-confirm-id="Hapus post sosial ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this social post?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No social posts yet" data-lang-id="Belum ada post sosial">Belum ada social post</strong>
                                        <span data-lang-en="Add the first post to track social media engagement." data-lang-id="Tambahkan post pertama untuk tracking engagement social media.">Tambahkan post pertama untuk tracking engagement social media.</span>
                                        <a href="{{ route('admin.marketing.social-engagements.create') }}" class="btn btn-primary" data-lang-en="Add Post" data-lang-id="Tambah Post">Add Post</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($posts->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info" data-lang-en="Showing {{ $posts->firstItem() }}-{{ $posts->lastItem() }} of {{ $posts->total() }} posts" data-lang-id="Menampilkan {{ $posts->firstItem() }}-{{ $posts->lastItem() }} dari {{ $posts->total() }} post">
                        Menampilkan {{ $posts->firstItem() }}-{{ $posts->lastItem() }} dari {{ $posts->total() }} post
                    </div>
                    <div class="pagination-links">
                        @if ($posts->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</span>
                        @else
                            <a href="{{ $posts->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</a>
                        @endif

                        @foreach ($posts->getUrlRange(max(1, $posts->currentPage() - 2), min($posts->lastPage(), $posts->currentPage() + 2)) as $page => $url)
                            @if ($page === $posts->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($posts->hasMorePages())
                            <a href="{{ $posts->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Berikutnya">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Berikutnya">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
