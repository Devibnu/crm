@extends('admin.layouts.app')

@section('title', $customer->name.' - Customer - Krakatau CRM')

@section('content')
    @php
        $recentInteractions = $customer->interactions()
            ->orderByRaw('interaction_at IS NULL')
            ->orderByDesc('interaction_at')
            ->latest('id')
            ->limit(5)
            ->get();

        $recentPreferences = $customer->preferences()
            ->latest()
            ->limit(5)
            ->get();

        $recentBehaviors = $customer->behaviors()
            ->orderByDesc('last_activity_at')
            ->latest('id')
            ->limit(5)
            ->get();

        $recentTransactions = $customer->transactions()
            ->orderByRaw('closing_date IS NULL')
            ->orderByDesc('closing_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $recentSalesActivities = $recentSalesActivities ?? collect();
        $recentQuotations = $recentQuotations ?? collect();
    @endphp
    <span hidden data-doc-title-en="{{ $customer->name }} - Customer - Krakatau CRM" data-doc-title-id="{{ $customer->name }} - Customer - Krakatau CRM"></span>

    <section class="service-page customer-list-page customer-360-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'user'])
            </div>
            <div>
                <h1 data-lang-en="Customer Profile 360" data-lang-id="Customer Profile 360">Customer Profile 360</h1>
                <p data-lang-en="A single customer view to see the full customer profile on one page." data-lang-id="Single Customer View untuk melihat profil customer secara lengkap dalam satu halaman.">Single Customer View untuk melihat profil customer secara lengkap dalam satu halaman.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="customer-360-grid">
            <aside class="card customer-360-summary">
                <h2 data-lang-en="Profile Summary" data-lang-id="Ringkasan Profil">Profile Summary</h2>
                <div class="customer-360-name-row">
                    <strong>{{ $customer->name }}</strong>
                    <span class="status-badge status-{{ $customer->status }}">{{ ucfirst($customer->status) }}</span>
                </div>

                <div class="customer-360-meta">
                    <div><span data-lang-en="Email" data-lang-id="Email">Email</span><strong>{{ $customer->email ?: '-' }}</strong></div>
                    <div><span data-lang-en="Phone" data-lang-id="Telepon">Phone</span><strong>{{ $customer->phone ?: '-' }}</strong></div>
                    <div><span data-lang-en="WhatsApp" data-lang-id="WhatsApp">WhatsApp</span><strong>{{ $customer->whatsapp ?: '-' }}</strong></div>
                    <div><span data-lang-en="Company Name" data-lang-id="Nama Perusahaan">Company Name</span><strong>{{ $customer->company_name ?: '-' }}</strong></div>
                    <div><span data-lang-en="Owner" data-lang-id="Owner">Owner</span><strong>{{ $customer->owner_name ?: '-' }}</strong></div>
                    <div><span data-lang-en="Source" data-lang-id="Sumber">Source</span><strong>{{ $customer->source ?: '-' }}</strong></div>
                    <div><span data-lang-en="Created At" data-lang-id="Dibuat Pada">Created At</span><strong>{{ $customer->created_at?->format('d M Y H:i') }}</strong></div>
                </div>

                <div class="customer-360-summary-actions">
                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary" data-lang-en="Edit Customer" data-lang-id="Edit Customer">Edit Customer</a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-muted" data-lang-en="Back to List" data-lang-id="Kembali ke Daftar">Back to List</a>
                </div>
            </aside>

            <section class="card customer-360-tabs-card">
                <ul class="nav nav-tabs customer-360-tabs" role="tablist" aria-label="Customer 360 tabs" data-title-en="Customer 360 tabs" data-title-id="Tab Customer 360">
                    <li role="presentation"><button type="button" class="nav-link active customer-tab-btn" role="tab" aria-selected="true" data-tab="overview" data-lang-en="Overview" data-lang-id="Ringkasan">Overview</button></li>
                    <li role="presentation"><button type="button" class="nav-link customer-tab-btn" role="tab" aria-selected="false" data-tab="notes" data-lang-en="Notes" data-lang-id="Catatan">Notes</button></li>
                    <li role="presentation"><button type="button" class="nav-link customer-tab-btn" role="tab" aria-selected="false" data-tab="preferences" data-lang-en="Preferences" data-lang-id="Preferensi">Preferences</button></li>
                    <li role="presentation"><button type="button" class="nav-link customer-tab-btn" role="tab" aria-selected="false" data-tab="deals" data-lang-en="Deals" data-lang-id="Deal">Deals</button></li>
                    <li role="presentation"><button type="button" class="nav-link customer-tab-btn" role="tab" aria-selected="false" data-tab="interactions" data-lang-en="Interactions" data-lang-id="Interaksi">Interactions</button></li>
                    <li role="presentation"><button type="button" class="nav-link customer-tab-btn" role="tab" aria-selected="false" data-tab="sales-activities" data-lang-en="Sales Activities" data-lang-id="Aktivitas Sales">Sales Activities</button></li>
                    <li role="presentation"><button type="button" class="nav-link customer-tab-btn" role="tab" aria-selected="false" data-tab="transactions" data-lang-en="Transactions" data-lang-id="Transaksi">Transactions</button></li>
                    <li role="presentation"><button type="button" class="nav-link customer-tab-btn" role="tab" aria-selected="false" data-tab="behavior" data-lang-en="Behavior" data-lang-id="Perilaku">Behavior</button></li>
                </ul>

                <div class="tab-content customer-360-tab-content-wrap">
                    <div class="tab-pane active show customer-tab-content" data-panel="overview" role="tabpanel">
                        <div class="customer-show-grid">
                            <div><strong data-lang-en="Name" data-lang-id="Nama">Name</strong><span>{{ $customer->name }}</span></div>
                            <div><strong data-lang-en="Email" data-lang-id="Email">Email</strong><span>{{ $customer->email ?: '-' }}</span></div>
                            <div><strong data-lang-en="Phone" data-lang-id="Telepon">Phone</strong><span>{{ $customer->phone ?: '-' }}</span></div>
                            <div><strong data-lang-en="WhatsApp" data-lang-id="WhatsApp">WhatsApp</strong><span>{{ $customer->whatsapp ?: '-' }}</span></div>
                            <div><strong data-lang-en="Company" data-lang-id="Perusahaan">Company</strong><span>{{ $customer->company_name ?: '-' }}</span></div>
                            <div><strong data-lang-en="Owner" data-lang-id="Owner">Owner</strong><span>{{ $customer->owner_name ?: '-' }}</span></div>
                            <div><strong data-lang-en="Source" data-lang-id="Sumber">Source</strong><span>{{ $customer->source ?: '-' }}</span></div>
                            <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span><span class="status-badge status-{{ $customer->status }}">{{ ucfirst($customer->status) }}</span></span></div>
                            <div><strong data-lang-en="Created At" data-lang-id="Dibuat Pada">Created At</strong><span>{{ $customer->created_at?->format('d M Y H:i') }}</span></div>
                            <div><strong data-lang-en="Updated At" data-lang-id="Diperbarui Pada">Updated At</strong><span>{{ $customer->updated_at?->format('d M Y H:i') }}</span></div>
                        </div>
                    </div>

                    <div class="tab-pane customer-tab-content" data-panel="notes" role="tabpanel" hidden>
                        <article class="card customer-placeholder-card customer-notes-card">
                            <h3 data-lang-en="Notes" data-lang-id="Catatan">Notes</h3>
                            <p>{{ $customer->notes ?: '' }}@unless($customer->notes)<span data-lang-en="No notes available" data-lang-id="Belum ada catatan">No notes available</span>@endunless</p>
                        </article>
                    </div>

                    <div class="tab-pane customer-tab-content" data-panel="preferences" role="tabpanel" hidden>
                        <div class="customer-interactions-head">
                            <a href="{{ route('admin.customers.preferences.create', $customer) }}" class="btn btn-primary btn-sm" data-lang-en="Add Preference" data-lang-id="Tambah Preferensi">Add Preference</a>
                        </div>

                        @if ($recentPreferences->isEmpty())
                            <article class="card customer-placeholder-card">
                                <p data-lang-en="No preferences available" data-lang-id="Belum ada preferensi">No preferences available</p>
                            </article>
                        @else
                            <div class="customer-table-wrap">
                                <table class="customer-table customer-interactions-table">
                                    <thead>
                                        <tr>
                                            <th>Preferred Channel</th>
                                            <th>Product Interest</th>
                                            <th>Consent</th>
                                            <th>Segment</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentPreferences as $preference)
                                            <tr>
                                                <td>{{ ucfirst($preference->preferred_channel) }}</td>
                                                <td>{{ $preference->product_interest ?: '-' }}</td>
                                                <td>
                                                    @if ($preference->communication_consent)
                                                        <span class="status-badge status-active">Yes</span>
                                                    @else
                                                        <span class="status-badge status-inactive">No</span>
                                                    @endif
                                                </td>
                                                <td>{{ $preference->segment ?: '-' }}</td>
                                                <td>{{ \Illuminate\Support\Str::limit($preference->notes ?: '-', 90) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane customer-tab-content" data-panel="deals" role="tabpanel" hidden>
                        <div class="customer-interactions-head">
                            <a href="{{ route('admin.sales.deals.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm" data-lang-en="Add Quotation" data-lang-id="Tambah Quotation">Add Quotation</a>
                        </div>

                        <h3 data-lang-en="Recent Quotations / Deals" data-lang-id="Quotation / Deal Terbaru">Recent Quotations / Deals</h3>

                        @if ($recentQuotations->isEmpty())
                            <article class="card customer-placeholder-card">
                                <p data-lang-en="No quotations available" data-lang-id="Belum ada quotation">No quotations available</p>
                            </article>
                        @else
                            <div class="customer-table-wrap">
                                <table class="customer-table customer-interactions-table sales-table">
                                    <thead>
                                        <tr>
                                            <th>Quote Number</th>
                                            <th>Title</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentQuotations as $quotation)
                                            <tr>
                                                <td><a href="{{ route('admin.sales.deals.show', $quotation) }}" class="sales-title-link">{{ $quotation->quote_number }}</a></td>
                                                <td>{{ $quotation->title }}</td>
                                                <td>Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</td>
                                                <td><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane customer-tab-content" data-panel="interactions" role="tabpanel" hidden>
                        <div class="customer-interactions-head">
                            <a href="{{ route('admin.customers.interactions.create', $customer) }}" class="btn btn-primary btn-sm" data-lang-en="Add Interaction" data-lang-id="Tambah Interaksi">Add Interaction</a>
                        </div>

                        @if ($recentInteractions->isEmpty())
                            <article class="card customer-placeholder-card">
                                <p data-lang-en="No interactions available" data-lang-id="Belum ada interaksi">No interactions available</p>
                            </article>
                        @else
                            <div class="customer-table-wrap">
                                <table class="customer-table customer-interactions-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                            <th>Handled By</th>
                                            <th>Outcome</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentInteractions as $interaction)
                                            <tr>
                                                <td><span class="status-badge status-new">{{ ucwords(str_replace('_', ' ', $interaction->type)) }}</span></td>
                                                <td>
                                                    <div>{{ $interaction->subject }}</div>
                                                    <small>{{ \Illuminate\Support\Str::limit($interaction->description ?: '-', 70) }}</small>
                                                </td>
                                                <td>{{ $interaction->interaction_at?->format('d M Y H:i') ?: '-' }}</td>
                                                <td>{{ $interaction->handled_by ?: '-' }}</td>
                                                <td>{{ $interaction->outcome ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane customer-tab-content" data-panel="sales-activities" role="tabpanel" hidden>
                        <div class="customer-interactions-head">
                            <a href="{{ route('admin.sales.activities.create', ['related_type' => 'customer', 'related_id' => $customer->id]) }}" class="btn btn-primary btn-sm" data-lang-en="Add Activity" data-lang-id="Tambah Aktivitas">Add Activity</a>
                        </div>

                        @if ($recentSalesActivities->isEmpty())
                            <article class="card customer-placeholder-card">
                                <p data-lang-en="No sales activities available" data-lang-id="Belum ada aktivitas sales">No sales activities available</p>
                            </article>
                        @else
                            <div class="customer-table-wrap">
                                <table class="customer-table customer-interactions-table sales-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Subject</th>
                                            <th>Activity Date</th>
                                            <th>Assigned To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentSalesActivities as $activity)
                                            <tr>
                                                <td><span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></td>
                                                <td>
                                                    <div>{{ $activity->subject }}</div>
                                                    <small>{{ \Illuminate\Support\Str::limit($activity->description ?: '-', 70) }}</small>
                                                </td>
                                                <td>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</td>
                                                <td>{{ $activity->assigned_to ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane customer-tab-content" data-panel="transactions" role="tabpanel" hidden>
                        <div class="customer-interactions-head">
                            <a href="{{ route('admin.customers.transactions.create', $customer) }}" class="btn btn-primary btn-sm" data-lang-en="Add Transaction" data-lang-id="Tambah Transaksi">Add Transaction</a>
                        </div>

                        @if ($recentTransactions->isEmpty())
                            <article class="card customer-placeholder-card">
                                <p data-lang-en="No transactions available" data-lang-id="Belum ada transaksi">No transactions available</p>
                            </article>
                        @else
                            <div class="customer-table-wrap">
                                <table class="customer-table customer-interactions-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentTransactions as $transaction)
                                            <tr>
                                                <td>
                                                    <div>{{ $transaction->title }}</div>
                                                    <small>{{ \Illuminate\Support\Str::limit($transaction->description ?: '-', 70) }}</small>
                                                </td>
                                                <td>Rp {{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                                                <td><span class="status-badge status-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span></td>
                                                <td>{{ $transaction->closing_date?->format('d M Y') ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane customer-tab-content" data-panel="behavior" role="tabpanel" hidden>
                        <div class="customer-interactions-head">
                            <a href="{{ route('admin.customers.behavior.create', $customer) }}" class="btn btn-primary btn-sm" data-lang-en="Add Behavior" data-lang-id="Tambah Perilaku">Add Behavior</a>
                        </div>

                        @if ($recentBehaviors->isEmpty())
                            <article class="card customer-placeholder-card">
                                <p data-lang-en="No behavior data available" data-lang-id="Belum ada data perilaku">No behavior data available</p>
                            </article>
                        @else
                            <div class="customer-table-wrap">
                                <table class="customer-table customer-interactions-table">
                                    <thead>
                                        <tr>
                                            <th>Lifecycle Stage</th>
                                            <th>Engagement Score</th>
                                            <th>Last Activity</th>
                                            <th>Product Interest</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentBehaviors as $behavior)
                                            <tr>
                                                <td><span class="status-badge status-new">{{ ucfirst($behavior->lifecycle_stage) }}</span></td>
                                                <td>
                                                    <div class="behavior-score-wrap">
                                                        <span class="behavior-score-value">{{ $behavior->engagement_score }}/100</span>
                                                        <div class="behavior-score-track">
                                                            <span style="width: {{ min(max($behavior->engagement_score, 0), 100) }}%"></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $behavior->last_activity_at?->format('d M Y H:i') ?: '-' }}</td>
                                                <td>{{ $behavior->product_interest ?: '-' }}</td>
                                                <td>{{ \Illuminate\Support\Str::limit($behavior->behavior_notes ?: '-', 90) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </section>

    <style>
        .customer-360-grid {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(0, 2fr);
            gap: 18px;
        }

        .customer-360-summary,
        .customer-360-tabs-card {
            padding: 20px;
        }

        .customer-360-summary {
            height: fit-content;
        }

        .customer-360-summary h2 {
            margin: 0 0 14px;
            font-size: 20px;
        }

        .customer-360-name-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 16px;
        }

        .customer-360-name-row strong {
            font-size: 19px;
            line-height: 1.4;
        }

        .customer-360-meta {
            display: grid;
            gap: 10px;
        }

        .customer-360-meta div {
            border: 1px solid #f0eff6;
            border-radius: 6px;
            padding: 10px 12px;
            display: grid;
            gap: 4px;
        }

        .customer-360-meta span {
            font-size: 12px;
            color: #6f6b7d;
        }

        .customer-360-meta strong {
            font-size: 14px;
            font-weight: 500;
        }

        .customer-360-summary-actions {
            display: grid;
            gap: 8px;
            margin-top: 14px;
        }

        .customer-360-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
            padding: 0 0 12px;
            list-style: none;
            border-bottom: 1px solid #e7e5ef;
        }

        .customer-360-tabs li {
            margin: 0;
        }

        .customer-360-tab-content-wrap {
            padding-top: 16px;
        }

        .customer-360-tabs .customer-tab-btn {
            position: relative;
            border: 1px solid transparent;
            border-radius: 6px 6px 0 0;
            background: #f8f7fa;
            color: #5d596c;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.1;
            cursor: pointer;
            transition: background-color .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .customer-360-tabs .customer-tab-btn:hover {
            border-color: #ddd9eb;
            background: #f3f2f7;
            color: #4c4861;
        }

        .customer-360-tabs .customer-tab-btn:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 3px rgba(115, 103, 240, .16);
        }

        .customer-360-tabs .customer-tab-btn::after {
            content: "";
            position: absolute;
            left: 10px;
            right: 10px;
            bottom: -13px;
            height: 3px;
            border-radius: 999px;
            background: transparent;
            transition: background-color .2s ease;
        }

        .customer-360-tabs .customer-tab-btn.active {
            border-color: #7367f0;
            background: #fff;
            color: #7367f0;
            box-shadow: 0 3px 10px rgba(115, 103, 240, .12);
        }

        .customer-360-tabs .customer-tab-btn.active::after {
            background: #7367f0;
        }

        .customer-tab-content {
            display: none;
        }

        .customer-tab-content.active,
        .customer-tab-content.show {
            display: block;
        }

        .customer-placeholder-card {
            border: 1px dashed #e7e5ef;
            box-shadow: none;
            padding: 18px;
        }

        .customer-placeholder-card p {
            margin: 0;
            color: #6f6b7d;
            font-size: 15px;
        }

        .customer-interactions-head {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }

        .customer-interactions-table {
            min-width: 640px;
        }

        .behavior-score-wrap {
            display: grid;
            gap: 6px;
            min-width: 130px;
        }

        .behavior-score-value {
            color: #6f6b7d;
            font-size: 12px;
            font-weight: 600;
        }

        .behavior-score-track {
            height: 8px;
            border-radius: 999px;
            background: #ece9ff;
            overflow: hidden;
        }

        .behavior-score-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #7367f0, #8f84ff);
        }

        .customer-notes-card h3 {
            margin: 0 0 8px;
            font-size: 17px;
            color: #4c4861;
        }

        .customer-notes-card p {
            margin: 0;
            white-space: pre-line;
        }

        .customer-coming-soon {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #fff4de;
            color: #ff9f43;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            padding: 5px 9px;
            margin-bottom: 10px;
        }

        @media (max-width: 920px) {
            .customer-360-grid {
                grid-template-columns: 1fr;
            }

            .customer-360-tabs {
                gap: 6px;
            }

            .customer-360-tabs .customer-tab-btn {
                border-radius: 6px;
                padding: 8px 12px;
            }

            .customer-360-tabs .customer-tab-btn::after {
                display: none;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabButtons = document.querySelectorAll('.customer-tab-btn');
            const tabPanels = document.querySelectorAll('.customer-tab-content');

            tabButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const target = button.getAttribute('data-tab');

                    tabButtons.forEach(function (btn) {
                        btn.classList.remove('active');
                        btn.setAttribute('aria-selected', 'false');
                    });

                    tabPanels.forEach(function (panel) {
                        const isMatch = panel.getAttribute('data-panel') === target;
                        panel.classList.toggle('active', isMatch);
                        panel.hidden = !isMatch;
                    });

                    button.classList.add('active');
                    button.setAttribute('aria-selected', 'true');
                });
            });
        });
    </script>
@endsection
