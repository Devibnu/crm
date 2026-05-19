@extends('admin.layouts.app')

@section('title', $quotation->title.' - Deal - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="{{ $quotation->title }} - Deal - Krakatau CRM" data-doc-title-id="{{ $quotation->title }} - Deal - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-deals-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'deal'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Offer Desk" data-lang-id="Offer Desk">Offer Desk</span>
                <h1 data-lang-en="Quotation Detail" data-lang-id="Detail Quotation">Quotation Detail</h1>
                <p data-lang-en="View quotation summary, negotiation status, customer, opportunity, and deal notes." data-lang-id="Lihat ringkasan penawaran, status negosiasi, customer, opportunity, dan catatan deal.">Lihat ringkasan penawaran, status negosiasi, customer, opportunity, dan catatan deal.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card sales-deals-show-shell">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $quotation->title }}</h2>
                    <p>{{ $quotation->quote_number }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
                    <a href="{{ route('admin.sales.deals.edit', $quotation) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                    <form method="POST" action="{{ route('admin.sales.deals.destroy', $quotation) }}" data-confirm-en="Delete this quotation?" data-confirm-id="Hapus quotation ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus quotation ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Amount" data-lang-id="Nominal">Amount</span>
                    <strong>Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</strong>
                </div>
                <div>
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <strong>{{ ucfirst($quotation->status) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Valid Until" data-lang-id="Berlaku Sampai">Valid Until</span>
                    <strong>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Quote Number" data-lang-id="Nomor Quotation">Quote Number</strong><span>{{ $quotation->quote_number }}</span></div>
                <div><strong data-lang-en="Title" data-lang-id="Judul">Title</strong><span>{{ $quotation->title }}</span></div>
                <div><strong data-lang-en="Issued At" data-lang-id="Diterbitkan Pada">Issued At</strong><span>{{ $quotation->issued_at?->format('d M Y') ?: '-' }}</span></div>
                <div><strong data-lang-en="Valid Until" data-lang-id="Berlaku Sampai">Valid Until</strong><span>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</span></div>
            </div>

            <div class="sales-relation-grid">
                <div class="customer-notes">
                    <h3 data-lang-en="Customer" data-lang-id="Customer">Customer</h3>
                    @if ($quotation->customer)
                        <p>
                            <a href="{{ route('admin.customers.show', $quotation->customer) }}" class="btn btn-sm btn-muted">{{ $quotation->customer->name }}</a>
                        </p>
                    @else
                        <p>-</p>
                    @endif
                </div>

                <div class="customer-notes">
                    <h3 data-lang-en="Opportunity" data-lang-id="Opportunity">Opportunity</h3>
                    @if ($quotation->opportunity)
                        <p>
                            <a href="{{ route('admin.sales.opportunities.show', $quotation->opportunity) }}" class="btn btn-sm btn-muted">{{ $quotation->opportunity->title }}</a>
                        </p>
                    @else
                        <p>-</p>
                    @endif
                </div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Notes" data-lang-id="Catatan">Notes</h3>
                <p>{{ $quotation->notes ?: '' }}@unless($quotation->notes)<span data-lang-en="No notes available" data-lang-id="Belum ada catatan">No notes available</span>@endunless</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
            </div>
        </article>
    </section>
@endsection
