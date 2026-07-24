@extends('admin.layouts.app')

@section('title', 'Customer Satisfaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">
                    @include('admin.partials.sidebar-icon', ['icon' => 'star'])
                </div>
                <div>
                    <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                    <h1>Customer Satisfaction</h1>
                    <p>Kelola survei kepuasan pelanggan dan tindak lanjut feedback.</p>
                    <div class="customer-profile-hero-meta" aria-label="Customer satisfaction summary">
                        <span>{{ number_format($summary['total']) }} total feedback</span>
                        <span>{{ number_format($summary['average_rating'], 2) }} average rating</span>
                        <span>{{ number_format($summary['follow_up_required']) }} follow up required</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                @can('csat.create')
                    <a href="{{ route('admin.service.customer-satisfaction.create') }}" class="btn lead-banner-cta">Add Feedback</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Feedback</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua survey response</small>
            </article>
            <article class="card sales-summary-card">
                <span>Average Rating</span>
                <strong>{{ number_format($summary['average_rating'], 2) }}</strong>
                <small>Skala 1 sampai 5</small>
            </article>
            <article class="card sales-summary-card">
                <span>Positive Sentiment</span>
                <strong>{{ number_format($summary['positive']) }}</strong>
                <small>Feedback bernada positif</small>
            </article>
            <article class="card sales-summary-card">
                <span>Follow Up Required</span>
                <strong>{{ number_format($summary['follow_up_required']) }}</strong>
                <small>Perlu tindak lanjut</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Feedback List</h2>
                    <p>Search customer name, ticket number, atau feedback.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.customer-satisfaction.index') }}" class="csat-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Customer, ticket number, feedback">
                </label>
                <label class="field">
                    <span>Rating</span>
                    <select name="rating">
                        <option value="">Semua rating</option>
                        @foreach ($ratingOptions as $rating)
                            <option value="{{ $rating }}" @selected((string) $selectedRating === (string) $rating)>{{ $rating }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Sentiment</span>
                    <select name="sentiment">
                        <option value="">Semua sentiment</option>
                        @foreach ($sentimentOptions as $sentiment)
                            <option value="{{ $sentiment }}" @selected($selectedSentiment === $sentiment)>{{ ucfirst($sentiment) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Channel</span>
                    <select name="survey_channel">
                        <option value="">Semua channel</option>
                        @foreach ($channelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Follow Up</span>
                    <select name="follow_up_required">
                        <option value="">Semua status</option>
                        @foreach ($followUpOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedFollowUp === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedRating || $selectedSentiment || $selectedChannel || $selectedFollowUp)
                        <a href="{{ route('admin.service.customer-satisfaction.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Ticket</th>
                            <th>Feedback</th>
                            <th>Rating</th>
                            <th>Sentiment</th>
                            <th>Channel</th>
                            <th>Submitted At</th>
                            <th>Follow Up</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($feedback as $item)
                            <tr>
                                <td>{{ $item->customer?->name ?: '-' }}</td>
                                <td>
                                    <strong class="sales-code">{{ $item->ticket?->ticket_number ?: '-' }}</strong>
                                    <small>{{ $item->ticket?->subject ?: '-' }}</small>
                                </td>
                                <td>{{ $item->feedback ?: '-' }}</td>
                                <td><span class="status-badge rating-{{ $item->rating }}">{{ $item->rating }}/5</span></td>
                                <td><span class="status-badge sentiment-{{ $item->sentiment }}">{{ ucfirst($item->sentiment) }}</span></td>
                                <td><span class="status-badge channel-{{ $item->survey_channel }}">{{ ucfirst($item->survey_channel) }}</span></td>
                                <td>{{ $item->submitted_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td><span class="status-badge status-{{ $item->follow_up_required ? 'pending' : 'active' }}">{{ $item->follow_up_required ? 'Required' : 'No follow up' }}</span></td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        @can('csat.view')
                                            <a href="{{ route('admin.service.customer-satisfaction.show', $item) }}" class="btn btn-sm btn-muted">View</a>
                                        @endcan
                                        @can('csat.update')
                                            <a href="{{ route('admin.service.customer-satisfaction.edit', $item) }}" class="btn btn-sm btn-primary">Edit</a>
                                        @endcan
                                        @can('csat.delete')
                                            <form method="POST" action="{{ route('admin.service.customer-satisfaction.destroy', $item) }}" onsubmit="return confirm('Delete feedback ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada feedback</strong>
                                        <span>Tambahkan feedback pertama untuk mulai memantau kepuasan pelanggan.</span>
                                        @can('csat.create')
                                            <a href="{{ route('admin.service.customer-satisfaction.create') }}" class="btn btn-primary">Add Feedback</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($feedback->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $feedback->firstItem() }}-{{ $feedback->lastItem() }} dari {{ $feedback->total() }} feedback
                    </div>
                    <div class="pagination-links">
                        @if ($feedback->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $feedback->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($feedback->getUrlRange(max(1, $feedback->currentPage() - 2), min($feedback->lastPage(), $feedback->currentPage() + 2)) as $page => $url)
                            @if ($page === $feedback->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($feedback->hasMorePages())
                            <a href="{{ $feedback->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
