@extends('admin.layouts.app')

@section('title', $broadcast->name.' - WhatsApp Broadcast - Krakatau CRM')

@section('content')
    @php($asRate = fn ($numerator, $denominator) => $denominator > 0 ? number_format(($numerator / $denominator) * 100, 2) . '%' : '0.00%')

    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="{{ $broadcast->name }} - WhatsApp Broadcast - Krakatau CRM" data-doc-title-id="{{ $broadcast->name }} - Broadcast WhatsApp - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1 data-lang-en="WhatsApp Broadcast Detail" data-lang-id="Detail Broadcast WhatsApp">WhatsApp Broadcast Detail</h1>
                <p data-lang-en="View recipients, status tracking, and WhatsApp campaign reply performance." data-lang-id="Lihat recipient, tracking status, dan performa balasan campaign WhatsApp.">Lihat recipients, status tracking, dan performa reply campaign WhatsApp.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $broadcast->name }}</h2>
                    <p data-lang-en="{{ $broadcast->marketingCampaign?->name ?: 'Without campaign' }}" data-lang-id="{{ $broadcast->marketingCampaign?->name ?: 'Tanpa campaign' }}">{{ $broadcast->marketingCampaign?->name ?: 'Without campaign' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge type-{{ $broadcast->target_type }}">{{ ucfirst($broadcast->target_type) }}</span>
                    <span class="status-badge status-{{ $broadcast->status }}">{{ ucfirst($broadcast->status) }}</span>
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.edit', $broadcast) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.destroy', $broadcast) }}" data-confirm-en="Delete this broadcast?" data-confirm-id="Hapus broadcast ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this broadcast?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Total Recipients" data-lang-id="Total Recipient">Total Recipients</span>
                    <strong>{{ number_format($broadcast->total_recipients) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Delivery Rate" data-lang-id="Rasio Terkirim">Delivery Rate</span>
                    <strong>{{ $asRate($broadcast->delivered_count, $broadcast->sent_count) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Read Rate" data-lang-id="Rasio Dibaca">Read Rate</span>
                    <strong>{{ $asRate($broadcast->read_count, $broadcast->delivered_count) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Reply Rate" data-lang-id="Rasio Balasan">Reply Rate</span>
                    <strong>{{ $asRate($broadcast->replied_count, $broadcast->total_recipients) }}</strong>
                </div>
            </div>

            <div class="dashboard-status-list" style="margin-bottom: 20px;">
                @foreach ($statusTracking as $row)
                    <div>
                        <span data-lang-en="{{ $row['label'] }}" data-lang-id="{{ $row['label_id'] ?? $row['label'] }}">{{ $row['label'] }}</span>
                        <strong>{{ number_format($row['value']) }}</strong>
                    </div>
                @endforeach
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Broadcast Name" data-lang-id="Nama Broadcast">Broadcast Name</strong><span>{{ $broadcast->name }}</span></div>
                <div><strong data-lang-en="Related Campaign" data-lang-id="Campaign Terkait">Related Campaign</strong><span>{{ $broadcast->marketingCampaign?->name ?: '-' }}</span></div>
                <div><strong data-lang-en="Target Type" data-lang-id="Tipe Target">Target Type</strong><span>{{ ucfirst($broadcast->target_type) }}</span></div>
                <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span>{{ ucfirst($broadcast->status) }}</span></div>
                <div><strong data-lang-en="Scheduled At" data-lang-id="Dijadwalkan Pada">Scheduled At</strong><span>{{ $broadcast->scheduled_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Sent At" data-lang-id="Terkirim Pada">Sent At</strong><span>{{ $broadcast->sent_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</strong><span>{{ $broadcast->created_by ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Message Template" data-lang-id="Template Pesan">Message Template</h3>
                <p>{{ $broadcast->message_template }}</p>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Notes" data-lang-id="Catatan">Notes</h3>
                <p data-lang-en="{{ $broadcast->notes ?: 'No notes available' }}" data-lang-id="{{ $broadcast->notes ?: 'Belum ada catatan' }}">{{ $broadcast->notes ?: 'No notes available' }}</p>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Broadcast Recipients" data-lang-id="Recipient Broadcast">Broadcast Recipients</h2>
                    <p data-lang-en="Actual recipient list pulled from customers or leads based on the source." data-lang-id="Daftar recipient aktual yang diambil dari customer atau lead sesuai sumber.">Daftar recipient real yang diambil dari customer atau lead sesuai source.</p>
                </div>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Name" data-lang-id="Nama">Name</th>
                            <th data-lang-en="Phone" data-lang-id="Telepon">Phone</th>
                            <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Sent At" data-lang-id="Terkirim Pada">Sent At</th>
                            <th data-lang-en="Delivered At" data-lang-id="Sampai Pada">Delivered At</th>
                            <th data-lang-en="Read At" data-lang-id="Dibaca Pada">Read At</th>
                            <th data-lang-en="Replied At" data-lang-id="Dibalas Pada">Replied At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recipientRows as $recipient)
                            <tr>
                                <td>{{ $recipient->recipient_name }}</td>
                                <td>{{ $recipient->phone_number }}</td>
                                <td><span class="status-badge type-{{ $recipient->recipient_type }}">{{ ucfirst($recipient->recipient_type) }}</span></td>
                                <td><span class="status-badge status-{{ $recipient->status }}">{{ ucfirst($recipient->status) }}</span></td>
                                <td>{{ $recipient->sent_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $recipient->delivered_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $recipient->read_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $recipient->replied_at?->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty" data-lang-en="No recipients yet for this broadcast." data-lang-id="Belum ada recipient untuk broadcast ini.">Belum ada recipients untuk broadcast ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
