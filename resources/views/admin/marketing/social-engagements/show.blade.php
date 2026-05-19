@extends('admin.layouts.app')

@section('title', $post->post_title.' - Social Engagement - Krakatau CRM')

@section('content')
    @php
        $engagementWidth = min(100, (float) $post->engagement_rate);
        $scoreWidth = min(100, $engagementScore);
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="{{ $post->post_title }} - Social Engagement - Krakatau CRM" data-doc-title-id="{{ $post->post_title }} - Engagement Sosial - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'social'])
            </div>
            <div>
                <h1 data-lang-en="Social Media Engagement Detail" data-lang-id="Detail Engagement Media Sosial">Social Media Engagement Detail</h1>
                <p data-lang-en="View the content, publishing status, and social media engagement performance." data-lang-id="Lihat konten, status publikasi, dan performa engagement social media.">Lihat content, publishing status, dan engagement performance social media.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $post->post_title }}</h2>
                    <p>{{ ucfirst($post->platform) }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge platform-{{ $post->platform }}">{{ ucfirst($post->platform) }}</span>
                    <span class="status-badge status-{{ $post->status }}">{{ ucfirst($post->status) }}</span>
                    <a href="{{ route('admin.marketing.social-engagements.edit', $post) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.social-engagements.destroy', $post) }}" data-confirm-en="Delete this social post?" data-confirm-id="Hapus post sosial ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this social post?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            @if ($post->marketingCampaign)
                <div class="customer-notes">
                    <h3 data-lang-en="Campaign" data-lang-id="Campaign">Campaign</h3>
                    <p><a href="{{ route('admin.marketing.campaigns.show', $post->marketingCampaign) }}" class="btn btn-sm btn-muted">{{ $post->marketingCampaign->name }}</a></p>
                </div>
            @endif

            <div class="sales-detail-hero">
                <div><span data-lang-en="Likes" data-lang-id="Likes">Likes</span><strong>{{ number_format($post->likes_count) }}</strong></div>
                <div><span data-lang-en="Comments" data-lang-id="Komentar">Comments</span><strong>{{ number_format($post->comments_count) }}</strong></div>
                <div><span data-lang-en="Shares" data-lang-id="Share">Shares</span><strong>{{ number_format($post->shares_count) }}</strong></div>
                <div><span data-lang-en="Impressions" data-lang-id="Impression">Impressions</span><strong>{{ number_format($post->impressions_count) }}</strong></div>
            </div>

            <div class="social-rate-grid">
                <div class="customer-notes">
                    <h3 data-lang-en="Engagement Rate" data-lang-id="Engagement Rate">Engagement Rate</h3>
                    <strong>{{ number_format((float) $post->engagement_rate, 2) }}%</strong>
                    <div class="social-rate-track"><span style="width: {{ $engagementWidth }}%"></span></div>
                </div>
                <div class="customer-notes">
                    <h3 data-lang-en="Engagement Score Summary" data-lang-id="Ringkasan Skor Engagement">Engagement Score Summary</h3>
                    <strong>{{ number_format($engagementScore, 2) }}%</strong>
                    <div class="social-rate-track"><span style="width: {{ $scoreWidth }}%"></span></div>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Platform" data-lang-id="Platform">Platform</strong><span><span class="status-badge platform-{{ $post->platform }}">{{ ucfirst($post->platform) }}</span></span></div>
                <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span><span class="status-badge status-{{ $post->status }}">{{ ucfirst($post->status) }}</span></span></div>
                <div><strong data-lang-en="Posted At" data-lang-id="Diposting Pada">Posted At</strong><span>{{ $post->posted_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</strong><span>{{ $post->created_by ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Post URL" data-lang-id="URL Post">Post URL</h3>
                @if ($post->post_url)
                    <p><a href="{{ $post->post_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-muted">{{ $post->post_url }}</a></p>
                @else
                    <p>-</p>
                @endif
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Content" data-lang-id="Konten">Content</h3>
                <p data-lang-en="{{ $post->content ?: 'No content available' }}" data-lang-id="{{ $post->content ?: 'Belum ada konten' }}">{{ $post->content ?: 'No content available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.social-engagements.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
            </div>
        </article>
    </section>

    <style>
        .social-rate-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 16px;
        }

        .social-rate-grid strong {
            display: block;
            margin-bottom: 8px;
            color: #3b384c;
            font-size: 24px;
        }

        .social-rate-track {
            height: 9px;
            border-radius: 999px;
            background: #ece9ff;
            overflow: hidden;
        }

        .social-rate-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #28c76f, #7367f0);
        }

        @media (max-width: 720px) {
            .social-rate-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
