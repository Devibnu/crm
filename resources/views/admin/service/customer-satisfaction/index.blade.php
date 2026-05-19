@extends('admin.layouts.app')

@section('title', 'Customer Satisfaction - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Customer Satisfaction - Krakatau CRM" data-doc-title-id="Kepuasan Pelanggan - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'star'])
            </div>
            <div>
                <h1 data-lang-en="Customer Satisfaction" data-lang-id="Kepuasan Pelanggan">Customer Satisfaction</h1>
                <p data-lang-en="Manage customer satisfaction surveys and feedback follow-up." data-lang-id="Kelola survei kepuasan pelanggan dan tindak lanjut feedback.">Kelola survei kepuasan pelanggan dan tindak lanjut feedback.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Feedback" data-lang-id="Total Feedback">Total Feedback</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All survey responses" data-lang-id="Semua survey response">All survey responses</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Average Rating" data-lang-id="Rata-rata Rating">Average Rating</span>
                <strong>{{ number_format($summary['average_rating'], 2) }}</strong>
                <small data-lang-en="Scale from 1 to 5" data-lang-id="Skala 1 sampai 5">Scale from 1 to 5</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Positive Sentiment" data-lang-id="Sentimen Positif">Positive Sentiment</span>
                <strong>{{ number_format($summary['positive']) }}</strong>
                <small data-lang-en="Positive feedback" data-lang-id="Feedback bernada positif">Positive feedback</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Follow Up Required" data-lang-id="Perlu Tindak Lanjut">Follow Up Required</span>
                <strong>{{ number_format($summary['follow_up_required']) }}</strong>
                <small data-lang-en="Needs follow-up" data-lang-id="Perlu tindak lanjut">Needs follow-up</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Feedback List" data-lang-id="Daftar Feedback">Feedback List</h2>
                    <p data-lang-en="Search customer name, ticket number, or feedback." data-lang-id="Search customer name, ticket number, atau feedback.">Search customer name, ticket number, atau feedback.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.service.customer-satisfaction.create') }}" class="btn btn-primary" data-lang-en="Add Feedback" data-lang-id="Tambah Feedback">Add Feedback</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.customer-satisfaction.index') }}" class="csat-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Customer, ticket number, feedback" data-placeholder-en="Customer, ticket number, feedback" data-placeholder-id="Customer, ticket number, feedback">
                </label>
                <label class="field">
                    <span data-lang-en="Rating" data-lang-id="Rating">Rating</span>
                    <select name="rating">
                        <option value="" data-lang-en="All ratings" data-lang-id="Semua rating">Semua rating</option>
                        @foreach ($ratingOptions as $rating)
                            <option value="{{ $rating }}" @selected((string) $selectedRating === (string) $rating)>{{ $rating }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Sentiment" data-lang-id="Sentimen">Sentiment</span>
                    <select name="sentiment">
                        <option value="" data-lang-en="All sentiments" data-lang-id="Semua sentimen">Semua sentiment</option>
                        @foreach ($sentimentOptions as $sentiment)
                            <option value="{{ $sentiment }}" @selected($selectedSentiment === $sentiment)>{{ ucfirst($sentiment) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Channel" data-lang-id="Channel">Channel</span>
                    <select name="survey_channel">
                        <option value="" data-lang-en="All channels" data-lang-id="Semua channel">Semua channel</option>
                        @foreach ($channelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Follow Up" data-lang-id="Tindak Lanjut">Follow Up</span>
                    <select name="follow_up_required">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">Semua status</option>
                        @foreach ($followUpOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedFollowUp === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($search || $selectedRating || $selectedSentiment || $selectedChannel || $selectedFollowUp)
                        <a href="{{ route('admin.service.customer-satisfaction.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Customer" data-lang-id="Customer">Customer</th>
                            <th data-lang-en="Ticket" data-lang-id="Tiket">Ticket</th>
                            <th data-lang-en="Feedback" data-lang-id="Feedback">Feedback</th>
                            <th data-lang-en="Rating" data-lang-id="Rating">Rating</th>
                            <th data-lang-en="Sentiment" data-lang-id="Sentimen">Sentiment</th>
                            <th data-lang-en="Channel" data-lang-id="Channel">Channel</th>
                            <th data-lang-en="Submitted At" data-lang-id="Dikirim Pada">Submitted At</th>
                            <th data-lang-en="Follow Up" data-lang-id="Tindak Lanjut">Follow Up</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                <td><span class="status-badge status-{{ $item->follow_up_required ? 'pending' : 'active' }}"><span data-lang-en="{{ $item->follow_up_required ? 'Required' : 'No follow up' }}" data-lang-id="{{ $item->follow_up_required ? 'Diperlukan' : 'Tidak perlu follow up' }}">{{ $item->follow_up_required ? 'Required' : 'No follow up' }}</span></span></td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.service.customer-satisfaction.show', $item) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a>
                                        <a href="{{ route('admin.service.customer-satisfaction.edit', $item) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.service.customer-satisfaction.destroy', $item) }}" data-confirm-en="Delete this feedback?" data-confirm-id="Hapus feedback ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus feedback ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No feedback yet" data-lang-id="Belum ada feedback">No feedback yet</strong>
                                        <span data-lang-en="Add the first feedback to start monitoring customer satisfaction." data-lang-id="Tambahkan feedback pertama untuk mulai memantau kepuasan pelanggan.">Add the first feedback to start monitoring customer satisfaction.</span>
                                        <a href="{{ route('admin.service.customer-satisfaction.create') }}" class="btn btn-primary" data-lang-en="Add Feedback" data-lang-id="Tambah Feedback">Add Feedback</a>
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
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $feedback->firstItem() }}-{{ $feedback->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $feedback->total() }} <span data-lang-en="feedback items" data-lang-id="feedback">feedback items</span>
                    </div>
                    <div class="pagination-links">
                        @if ($feedback->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $feedback->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($feedback->getUrlRange(max(1, $feedback->currentPage() - 2), min($feedback->lastPage(), $feedback->currentPage() + 2)) as $page => $url)
                            @if ($page === $feedback->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($feedback->hasMorePages())
                            <a href="{{ $feedback->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
