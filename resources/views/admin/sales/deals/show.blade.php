@extends('admin.layouts.app')

@section('title', $quotation->title.' - Deal - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'deal'])
            </div>
            <div>
                <h1>Quotation Detail</h1>
                <p>Lihat ringkasan penawaran, status negosiasi, customer, opportunity, dan catatan deal.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $quotation->title }}</h2>
                    <p>{{ $quotation->quote_number }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
                    <a href="{{ route('admin.sales.deals.edit', $quotation) }}" class="btn btn-primary">Edit</a>
                    <form method="POST" action="{{ route('admin.sales.deals.destroy', $quotation) }}" onsubmit="return confirm('Delete quotation ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Amount</span>
                    <strong>Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</strong>
                </div>
                <div>
                    <span>Status</span>
                    <strong>{{ ucfirst($quotation->status) }}</strong>
                </div>
                <div>
                    <span>Valid Until</span>
                    <strong>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Quote Number</strong><span>{{ $quotation->quote_number }}</span></div>
                <div><strong>Title</strong><span>{{ $quotation->title }}</span></div>
                <div><strong>Issued At</strong><span>{{ $quotation->issued_at?->format('d M Y') ?: '-' }}</span></div>
                <div><strong>Valid Until</strong><span>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</span></div>
            </div>

            <div class="sales-relation-grid">
                <div class="customer-notes">
                    <h3>Customer</h3>
                    @if ($quotation->customer)
                        <p>
                            <a href="{{ route('admin.customers.show', $quotation->customer) }}" class="btn btn-sm btn-muted">{{ $quotation->customer->name }}</a>
                        </p>
                    @else
                        <p>-</p>
                    @endif
                </div>

                <div class="customer-notes">
                    <h3>Opportunity</h3>
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
                <h3>Notes</h3>
                <p>{{ $quotation->notes ?: 'No notes available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-muted">Back</a>
            </div>
        </article>
    </section>
@endsection
