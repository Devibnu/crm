@extends('admin.layouts.app')

@section('title', 'WhatsApp Reply Inbox - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="WhatsApp Reply Inbox - Krakatau CRM" data-doc-title-id="Inbox Balasan WhatsApp - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'inbox'])
            </div>
            <div>
                <h1 data-lang-en="{{ $title['en'] }}" data-lang-id="{{ $title['id'] }}">{{ $title['en'] }}</h1>
                <p data-lang-en="{{ $description['en'] }}" data-lang-id="{{ $description['id'] }}">{{ $description['en'] }}</p>
            </div>
        </article>

        <section class="sales-summary-grid">
            @foreach ($summaryCards as $card)
                <article class="card sales-summary-card">
                    <span data-lang-en="{{ $card['label_en'] }}" data-lang-id="{{ $card['label_id'] }}">{{ $card['label_en'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small data-lang-en="{{ $card['hint_en'] }}" data-lang-id="{{ $card['hint_id'] }}">{{ $card['hint_en'] }}</small>
                </article>
            @endforeach
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Reply Filters" data-lang-id="Filter Balasan">Reply Filters</h2>
                    <p data-lang-en="Search by sender, phone, or message. Filter by campaign and status for faster monitoring." data-lang-id="Cari berdasarkan pengirim, telepon, atau pesan. Filter berdasarkan campaign dan status untuk monitoring cepat.">Search by sender, phone, or message. Filter by campaign dan status untuk monitoring cepat.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.whatsapp-replies.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Sender, phone, or message" aria-label="Search WhatsApp replies" data-placeholder-en="Sender, phone, or message" data-placeholder-id="Pengirim, telepon, atau pesan" data-title-en="Search WhatsApp replies" data-title-id="Cari balasan WhatsApp">
                </label>
                <label class="field">
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <select name="status">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">All statuses</option>
                        @foreach ($statusOptions as $option)
                            <option value="{{ $option }}" @selected($selectedStatus === $option)>{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Campaign" data-lang-id="Campaign">Campaign</span>
                    <select name="campaign">
                        <option value="" data-lang-en="All campaigns" data-lang-id="Semua campaign">All campaigns</option>
                        @foreach ($campaignOptions as $option)
                            <option value="{{ $option }}" @selected($selectedCampaign === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Apply Filter" data-lang-id="Terapkan Filter">Apply Filter</button>
                    @if ($search || $selectedStatus || $selectedCampaign)
                        <a href="{{ route('admin.marketing.whatsapp-replies.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Sender Name" data-lang-id="Nama Pengirim">Sender Name</th>
                            <th data-lang-en="Phone Number" data-lang-id="Nomor Telepon">Phone Number</th>
                            <th data-lang-en="Message" data-lang-id="Pesan">Message</th>
                            <th data-lang-en="Related Campaign" data-lang-id="Campaign Terkait">Related Campaign</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Received At" data-lang-id="Diterima Pada">Received At</th>
                            <th data-lang-en="Source" data-lang-id="Sumber">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($replyRows as $reply)
                            <tr>
                                <td>{{ $reply['sender_name'] }}</td>
                                <td>{{ $reply['phone_number'] }}</td>
                                <td>{{ $reply['message'] }}</td>
                                <td>
                                    @if ($reply['uses_default_campaign_label'] ?? false)
                                        <span data-lang-en="Omnichannel WhatsApp" data-lang-id="WhatsApp Omnichannel">Omnichannel WhatsApp</span>
                                    @else
                                        {{ $reply['related_campaign'] }}
                                    @endif
                                </td>
                                <td><span class="status-badge status-{{ $reply['status'] }}">{{ ucfirst($reply['status']) }}</span></td>
                                <td>{{ optional($reply['received_at'])->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <span class="status-badge type-{{ $reply['source'] }}"
                                        data-lang-en="{{ $reply['source'] === 'broadcast' ? 'Broadcast' : 'Omnichannel' }}"
                                        data-lang-id="{{ $reply['source'] === 'broadcast' ? 'Broadcast' : 'Omnichannel' }}">
                                        {{ $reply['source'] === 'broadcast' ? 'Broadcast' : 'Omnichannel' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No WhatsApp replies yet" data-lang-id="Belum ada balasan WhatsApp">Belum ada balasan WhatsApp</strong>
                                        <span data-lang-en="Customer and lead replies will appear in this inbox after broadcasts are sent." data-lang-id="Balasan customer dan lead akan tampil di inbox ini setelah broadcast terkirim.">Balasan customer dan lead akan tampil di inbox ini setelah broadcast terkirim.</span>
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
