@extends('admin.layouts.app')

@section('title', 'Customer Profile 360 - ' . $branding->display_app_name)

@section('content')
<section class="service-page customer-list-page customer-profile-page">
    <article class="card service-card customer-list-card">
        <div class="service-card-icon">
            @include('admin.partials.sidebar-icon', ['icon' => 'user'])
        </div>
        <div>
            <h1>Customer Profile</h1>
            <p>Single Customer View untuk melihat profil customer secara lengkap dalam satu halaman.</p>
        </div>
    </article>

    @if (! $customer)
        <article class="card customer-profile-selector-card">
            <div class="customer-profile-section-head">
                <div>
                    <span>Customer Selector</span>
                    <h2>Pilih customer untuk melihat profil 360</h2>
                </div>
                <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">Tambah Customer</a>
            </div>

            <form method="GET" action="{{ route('admin.customers.profile') }}" class="customer-profile-search-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama, email, phone, WhatsApp, atau company..." aria-label="Cari customer">
                <button type="submit" class="btn btn-primary">Search Customer</button>
                @if ($search)
                    <a href="{{ route('admin.customers.profile') }}" class="btn btn-muted">Reset</a>
                @endif
            </form>

            @if ($customers->isEmpty())
                <div class="customer-profile-empty">
                    <strong>Belum ada customer ditemukan</strong>
                    <p>Tambahkan customer baru atau ubah kata kunci pencarian.</p>
                </div>
            @else
                <div class="customer-profile-customer-grid">
                    @foreach ($customers as $listedCustomer)
                        <article class="customer-profile-customer-card">
                            <div class="customer-profile-avatar">{{ strtoupper(substr($listedCustomer->name, 0, 1)) }}</div>
                            <div class="customer-profile-customer-main">
                                <div class="customer-profile-customer-title">
                                    <strong>{{ $listedCustomer->name }}</strong>
                                    <span class="status-badge status-{{ $listedCustomer->status }}">{{ ucfirst($listedCustomer->status) }}</span>
                                </div>
                                <p>{{ $listedCustomer->company_name ?: 'No company' }}</p>
                                <div class="customer-profile-customer-meta">
                                    <span>{{ $listedCustomer->email ?: '-' }}</span>
                                    <span>{{ $listedCustomer->phone ?: $listedCustomer->whatsapp ?: '-' }}</span>
                                    <span>{{ $listedCustomer->interactions_count }} interactions</span>
                                    <span>{{ $listedCustomer->transactions_count }} transactions</span>
                                </div>
                            </div>
                            <a href="{{ route('admin.customers.profile', ['customer_id' => $listedCustomer->id]) }}" class="btn btn-primary btn-sm">Lihat Profil</a>
                        </article>
                    @endforeach
                </div>

                @if ($customers->hasPages())
                    <div class="pagination-links customer-profile-pagination">
                        @if ($customers->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $customers->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($customers->getUrlRange(max(1, $customers->currentPage() - 2), min($customers->lastPage(), $customers->currentPage() + 2)) as $page => $url)
                            @if ($page === $customers->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($customers->hasMorePages())
                            <a href="{{ $customers->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                @endif
            @endif
        </article>
    @else
        <article class="card customer-profile-hero-card">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
                <div>
                    <div class="customer-profile-title-row">
                        <h2>{{ $customer->name }}</h2>
                        <span class="status-badge status-{{ $customer->status }}">{{ ucfirst($customer->status) }}</span>
                    </div>
                    <div class="customer-profile-hero-meta">
                        <span>{{ $customer->email ?: 'No email' }}</span>
                        <span>{{ $customer->phone ?: 'No phone' }}</span>
                        <span>WA: {{ $customer->whatsapp ?: '-' }}</span>
                        <span>{{ $customer->company_name ?: 'No company' }}</span>
                        <span>Created {{ $customer->created_at?->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions">
                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary">Edit Customer</a>
                <a href="{{ route('admin.customers.interactions.create', $customer) }}" class="btn btn-muted">Tambah Interaction</a>
                <a href="{{ route('admin.customers.transactions.create', $customer) }}" class="btn btn-muted">Tambah Transaction</a>
                <a href="{{ route('admin.customers.preferences.create', $customer) }}" class="btn btn-muted">Tambah Preference</a>
                <a href="{{ route('admin.customers.behavior.create', $customer) }}" class="btn btn-muted">Tambah Behavior</a>
            </div>
        </article>

        <div class="customer-profile-summary-grid">
            @foreach ([
                'Total Interactions' => $summary['interactions'],
                'Total Transactions' => $summary['transactions'],
                'Total Preferences' => $summary['preferences'],
                'Behavior Records' => $summary['behaviors'],
                'Total Opportunities' => $summary['opportunities'],
                'Total Quotations' => $summary['quotations'],
            ] as $label => $value)
                <article class="card customer-profile-summary-card">
                    <span>{{ $label }}</span>
                    <strong>{{ number_format($value) }}</strong>
                </article>
            @endforeach
        </div>

        <section class="customer-profile-content-grid">
            <article class="card customer-profile-overview-card">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Overview</span>
                        <h2>Informasi dasar customer</h2>
                    </div>
                </div>

                <div class="customer-profile-detail-grid">
                    <div><span>Email</span><strong>{{ $customer->email ?: '-' }}</strong></div>
                    <div><span>Phone</span><strong>{{ $customer->phone ?: '-' }}</strong></div>
                    <div><span>WhatsApp</span><strong>{{ $customer->whatsapp ?: '-' }}</strong></div>
                    <div><span>Company</span><strong>{{ $customer->company_name ?: '-' }}</strong></div>
                    <div><span>Owner</span><strong>{{ $customer->owner_name ?: '-' }}</strong></div>
                    <div><span>Source</span><strong>{{ $customer->source ?: '-' }}</strong></div>
                </div>

                <div class="customer-profile-notes">
                    <span>Notes</span>
                    <p>{{ $customer->notes ?: 'Belum ada notes untuk customer ini.' }}</p>
                </div>
            </article>

            <article class="card customer-profile-latest-card">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Latest Signals</span>
                        <h2>Aktivitas terakhir</h2>
                    </div>
                </div>

                <div class="customer-profile-latest-list">
                    <div>
                        <span>Latest Interaction</span>
                        <strong>{{ $latestInteraction?->subject ?: 'Belum ada interaction' }}</strong>
                        <small>{{ $latestInteraction?->interaction_at?->format('d M Y H:i') ?: '-' }}</small>
                    </div>
                    <div>
                        <span>Latest Transaction</span>
                        <strong>{{ $latestTransaction?->title ?: 'Belum ada transaction' }}</strong>
                        <small>{{ $latestTransaction ? 'Rp '.number_format((float) $latestTransaction->amount, 0, ',', '.') : '-' }}</small>
                    </div>
                    <div>
                        <span>Latest Preference</span>
                        <strong>{{ $latestPreference?->preferred_channel ? ucfirst($latestPreference->preferred_channel) : 'Belum ada preference' }}</strong>
                        <small>{{ $latestPreference?->product_interest ?: '-' }}</small>
                    </div>
                    <div>
                        <span>Latest Behavior</span>
                        <strong>{{ $latestBehavior?->lifecycle_stage ? ucfirst($latestBehavior->lifecycle_stage) : 'Belum ada behavior' }}</strong>
                        <small>{{ $latestBehavior?->engagement_score !== null ? $latestBehavior->engagement_score.'/100 engagement' : '-' }}</small>
                    </div>
                </div>
            </article>
        </section>

        <article class="card customer-profile-tabs-card">
            <div class="customer-profile-tabs" role="tablist" aria-label="Customer profile sections">
                @foreach (['interactions' => 'Interactions', 'transactions' => 'Transactions', 'preferences' => 'Preferences', 'behavior' => 'Behavior', 'opportunities' => 'Opportunities', 'quotations' => 'Quotations'] as $key => $label)
                    <button type="button" @class(['customer-profile-tab-btn', 'active' => $loop->first]) data-profile-tab="{{ $key }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>

            <div class="customer-profile-tab-panel" data-profile-panel="interactions">
                @include('admin.customers.profile_sections.table', [
                    'empty' => 'Belum ada interactions.',
                    'headers' => ['Type', 'Subject', 'Date', 'Handled By', 'Outcome'],
                    'rows' => $recentInteractions->map(fn ($interaction) => [
                        ucwords(str_replace('_', ' ', $interaction->type)),
                        $interaction->subject,
                        $interaction->interaction_at?->format('d M Y H:i') ?: '-',
                        $interaction->handled_by ?: '-',
                        $interaction->outcome ?: '-',
                    ]),
                ])
            </div>

            <div class="customer-profile-tab-panel" data-profile-panel="transactions" hidden>
                @include('admin.customers.profile_sections.table', [
                    'empty' => 'Belum ada transactions.',
                    'headers' => ['Title', 'Amount', 'Status', 'Closing Date'],
                    'rows' => $recentTransactions->map(fn ($transaction) => [
                        $transaction->title,
                        'Rp '.number_format((float) $transaction->amount, 0, ',', '.'),
                        ucfirst($transaction->status),
                        $transaction->closing_date?->format('d M Y') ?: '-',
                    ]),
                ])
            </div>

            <div class="customer-profile-tab-panel" data-profile-panel="preferences" hidden>
                @include('admin.customers.profile_sections.table', [
                    'empty' => 'Belum ada preferences.',
                    'headers' => ['Preferred Channel', 'Product Interest', 'Consent', 'Segment'],
                    'rows' => $recentPreferences->map(fn ($preference) => [
                        ucfirst($preference->preferred_channel),
                        $preference->product_interest ?: '-',
                        $preference->communication_consent ? 'Yes' : 'No',
                        $preference->segment ?: '-',
                    ]),
                ])
            </div>

            <div class="customer-profile-tab-panel" data-profile-panel="behavior" hidden>
                @include('admin.customers.profile_sections.table', [
                    'empty' => 'Belum ada behavior records.',
                    'headers' => ['Lifecycle Stage', 'Engagement Score', 'Last Activity', 'Product Interest'],
                    'rows' => $recentBehaviors->map(fn ($behavior) => [
                        ucfirst($behavior->lifecycle_stage),
                        $behavior->engagement_score.'/100',
                        $behavior->last_activity_at?->format('d M Y H:i') ?: '-',
                        $behavior->product_interest ?: '-',
                    ]),
                ])
            </div>

            <div class="customer-profile-tab-panel" data-profile-panel="opportunities" hidden>
                @include('admin.customers.profile_sections.table', [
                    'empty' => 'Belum ada opportunities.',
                    'headers' => ['Title', 'Value', 'Probability', 'Status'],
                    'rows' => $recentOpportunities->map(fn ($opportunity) => [
                        $opportunity->title,
                        'Rp '.number_format((float) $opportunity->estimated_value, 0, ',', '.'),
                        $opportunity->probability.'%',
                        ucfirst($opportunity->status),
                    ]),
                ])
            </div>

            <div class="customer-profile-tab-panel" data-profile-panel="quotations" hidden>
                @include('admin.customers.profile_sections.table', [
                    'empty' => 'Belum ada quotations.',
                    'headers' => ['Quote Number', 'Title', 'Amount', 'Status'],
                    'rows' => $recentQuotations->map(fn ($quotation) => [
                        $quotation->quote_number,
                        $quotation->title,
                        'Rp '.number_format((float) $quotation->amount, 0, ',', '.'),
                        ucfirst($quotation->status),
                    ]),
                ])
            </div>
        </article>

        <script>
            document.querySelectorAll('[data-profile-tab]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('[data-profile-tab]').forEach((tab) => {
                        tab.classList.toggle('active', tab === button);
                        tab.setAttribute('aria-selected', tab === button ? 'true' : 'false');
                    });

                    document.querySelectorAll('[data-profile-panel]').forEach((panel) => {
                        panel.hidden = panel.dataset.profilePanel !== button.dataset.profileTab;
                    });
                });
            });
        </script>
    @endif
</section>
@endsection
