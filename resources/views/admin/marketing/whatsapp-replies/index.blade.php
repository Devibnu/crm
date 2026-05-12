@extends('admin.layouts.app')

@section('title', 'WhatsApp Reply Inbox - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'inbox'])
            </div>
            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
            </div>
        </article>

        <section class="sales-summary-grid">
            @foreach ($summaryCards as $card)
                <article class="card sales-summary-card">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small>{{ $card['hint'] }}</small>
                </article>
            @endforeach
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Reply Filters</h2>
                    <p>Search by sender, phone, or message. Filter by campaign dan status untuk monitoring cepat.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.whatsapp-replies.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Sender, phone, or message" aria-label="Search WhatsApp replies">
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $option)
                            <option value="{{ $option }}" @selected($selectedStatus === $option)>{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Campaign</span>
                    <select name="campaign">
                        <option value="">All campaigns</option>
                        @foreach ($campaignOptions as $option)
                            <option value="{{ $option }}" @selected($selectedCampaign === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($search || $selectedStatus || $selectedCampaign)
                        <a href="{{ route('admin.marketing.whatsapp-replies.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Sender Name</th>
                            <th>Phone Number</th>
                            <th>Message</th>
                            <th>Related Campaign</th>
                            <th>Status</th>
                            <th>Received At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($replyRows as $reply)
                            <tr>
                                <td>{{ $reply['sender_name'] }}</td>
                                <td>{{ $reply['phone_number'] }}</td>
                                <td>{{ $reply['message'] }}</td>
                                <td>{{ $reply['related_campaign'] }}</td>
                                <td><span class="status-badge status-{{ $reply['status'] }}">{{ ucfirst($reply['status']) }}</span></td>
                                <td>{{ optional($reply['received_at'])->format('d M Y H:i') ?: '-' }}</td>
                                <td><span class="status-badge type-{{ $reply['source'] }}">{{ ucfirst($reply['source']) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada balasan WhatsApp</strong>
                                        <span>Balasan customer dan lead akan tampil di inbox ini setelah broadcast terkirim.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection