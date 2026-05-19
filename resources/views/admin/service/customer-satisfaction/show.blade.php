@extends('admin.layouts.app')

@section('title', 'Customer Satisfaction Detail - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Customer Satisfaction Detail - Krakatau CRM" data-doc-title-id="Detail Kepuasan Pelanggan - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'star'])
            </div>
            <div>
                <h1 data-lang-en="Customer Satisfaction Detail" data-lang-id="Detail Kepuasan Pelanggan">Customer Satisfaction Detail</h1>
                <p data-lang-en="Customer satisfaction survey detail, sentiment, channel, and feedback follow-up." data-lang-id="Detail survei kepuasan pelanggan, sentiment, channel, dan tindak lanjut feedback.">Detail survei kepuasan pelanggan, sentiment, channel, dan tindak lanjut feedback.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $satisfaction->customer?->name ?: '' }}@unless($satisfaction->customer?->name)<span data-lang-en="Customer Feedback" data-lang-id="Feedback Customer">Customer Feedback</span>@endunless</h2>
                    <p>{{ $satisfaction->ticket?->ticket_number ?: '' }}@unless($satisfaction->ticket?->ticket_number)<span data-lang-en="No ticket linked" data-lang-id="Belum terhubung ke tiket">No ticket linked</span>@endunless</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge sentiment-{{ $satisfaction->sentiment }}">{{ ucfirst($satisfaction->sentiment) }}</span>
                    <a href="{{ route('admin.service.customer-satisfaction.edit', $satisfaction) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Rating" data-lang-id="Rating">Rating</span>
                    <strong>{{ $satisfaction->rating }}/5</strong>
                </div>
                <div>
                    <span data-lang-en="Sentiment" data-lang-id="Sentimen">Sentiment</span>
                    <strong>{{ ucfirst($satisfaction->sentiment) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Channel" data-lang-id="Channel">Channel</span>
                    <strong>{{ ucfirst($satisfaction->survey_channel) }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Customer" data-lang-id="Customer">Customer</strong><span>{{ $satisfaction->customer?->name ?: '-' }}</span></div>
                <div><strong data-lang-en="Ticket" data-lang-id="Tiket">Ticket</strong><span>{{ $satisfaction->ticket?->ticket_number ?: '-' }}</span></div>
                <div><strong data-lang-en="Ticket Subject" data-lang-id="Subjek Tiket">Ticket Subject</strong><span>{{ $satisfaction->ticket?->subject ?: '-' }}</span></div>
                <div><strong data-lang-en="Rating" data-lang-id="Rating">Rating</strong><span>{{ $satisfaction->rating }}/5</span></div>
                <div><strong data-lang-en="Survey Channel" data-lang-id="Channel Survei">Survey Channel</strong><span>{{ ucfirst($satisfaction->survey_channel) }}</span></div>
                <div><strong data-lang-en="Sentiment" data-lang-id="Sentimen">Sentiment</strong><span>{{ ucfirst($satisfaction->sentiment) }}</span></div>
                <div><strong data-lang-en="Submitted At" data-lang-id="Dikirim Pada">Submitted At</strong><span>{{ $satisfaction->submitted_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Follow Up Required" data-lang-id="Perlu Tindak Lanjut">Follow Up Required</strong><span><span data-lang-en="{{ $satisfaction->follow_up_required ? 'Yes' : 'No' }}" data-lang-id="{{ $satisfaction->follow_up_required ? 'Ya' : 'Tidak' }}">{{ $satisfaction->follow_up_required ? 'Yes' : 'No' }}</span></span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Feedback" data-lang-id="Feedback">Feedback</h3>
                <p>{{ $satisfaction->feedback ?: '' }}@unless($satisfaction->feedback)<span data-lang-en="No feedback available" data-lang-id="Belum ada feedback">No feedback available</span>@endunless</p>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Follow Up Notes" data-lang-id="Catatan Tindak Lanjut">Follow Up Notes</h3>
                <p>{{ $satisfaction->follow_up_notes ?: '' }}@unless($satisfaction->follow_up_notes)<span data-lang-en="No follow up notes available" data-lang-id="Belum ada catatan tindak lanjut">No follow up notes available</span>@endunless</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.service.customer-satisfaction.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                <form method="POST" action="{{ route('admin.service.customer-satisfaction.destroy', $satisfaction) }}" data-confirm-en="Delete this feedback?" data-confirm-id="Hapus feedback ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus feedback ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
