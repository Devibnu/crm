@extends('admin.layouts.app')

@section('title', $opportunity->title.' - Opportunity - Krakatau CRM')

@section('content')
    @php
        $probability = min(max((int) $opportunity->probability, 0), 100);
    @endphp
    <span hidden data-doc-title-en="{{ $opportunity->title }} - Opportunity - Krakatau CRM" data-doc-title-id="{{ $opportunity->title }} - Opportunity - Krakatau CRM"></span>

    <section class="service-page customer-list-page sales-opportunities-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'opportunity'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Pipeline Studio" data-lang-id="Pipeline Studio">Pipeline Studio</span>
                <h1 data-lang-en="Opportunity Management" data-lang-id="Manajemen Opportunity">Opportunity Management</h1>
                <p data-lang-en="Manage business opportunities and discovery processes." data-lang-id="Kelola peluang bisnis dan proses discovery.">Kelola peluang bisnis dan proses discovery.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card sales-opportunities-show-shell">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $opportunity->title }}</h2>
                    <p>{{ $opportunity->company_name ?: '' }}@unless($opportunity->company_name)<span data-lang-en="No company name" data-lang-id="Tanpa nama perusahaan">No company name</span>@endunless</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Estimated Value" data-lang-id="Estimasi Nilai">Estimated Value</span>
                    <strong>Rp {{ number_format((float) $opportunity->estimated_value, 2, ',', '.') }}</strong>
                </div>
                <div>
                    <span data-lang-en="Probability" data-lang-id="Probabilitas">Probability</span>
                    <strong>{{ $probability }}%</strong>
                </div>
                <div>
                    <span data-lang-en="Expected Close" data-lang-id="Estimasi Closing">Expected Close</span>
                    <strong>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Company" data-lang-id="Perusahaan">Company</strong><span>{{ $opportunity->company_name ?: '-' }}</span></div>
                <div><strong data-lang-en="Contact" data-lang-id="Kontak">Contact</strong><span>{{ $opportunity->contact_name ?: '-' }}</span></div>
                <div><strong data-lang-en="Estimated Value" data-lang-id="Estimasi Nilai">Estimated Value</strong><span>Rp {{ number_format((float) $opportunity->estimated_value, 2, ',', '.') }}</span></div>
                <div><strong data-lang-en="Expected Close" data-lang-id="Estimasi Closing">Expected Close</strong><span>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</span></div>
                <div><strong data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</strong><span>{{ $opportunity->assigned_to ?: '-' }}</span></div>
                <div><strong data-lang-en="Created At" data-lang-id="Dibuat Pada">Created At</strong><span>{{ $opportunity->created_at?->format('d M Y H:i') }}</span></div>
            </div>

            <div class="customer-notes sales-probability-panel">
                <h3 data-lang-en="Probability" data-lang-id="Probabilitas">Probability</h3>
                <div class="sales-probability-track">
                    <span class="sales-probability-label">{{ $probability }}%</span>
                    <div class="sales-probability-bar">
                        <span style="width:{{ $probability }}%;"></span>
                    </div>
                </div>
            </div>

            @if ($opportunity->lead)
                <div class="customer-notes">
                    <h3 data-lang-en="Related Lead" data-lang-id="Lead Terkait">Related Lead</h3>
                    <p><a href="{{ route('admin.sales.leads.show', $opportunity->lead) }}" class="btn btn-sm btn-muted">{{ $opportunity->lead->name }}</a></p>
                </div>
            @endif

            @if ($opportunity->customer)
                <div class="customer-notes">
                    <h3 data-lang-en="Related Customer" data-lang-id="Customer Terkait">Related Customer</h3>
                    <p><a href="{{ route('admin.customers.show', $opportunity->customer) }}" class="btn btn-sm btn-muted">{{ $opportunity->customer->name }}</a></p>
                </div>
            @endif

            <div class="customer-notes">
                <div class="sales-section-head">
                    <div>
                        <h3 data-lang-en="Recent Quotations" data-lang-id="Quotation Terbaru">Recent Quotations</h3>
                    </div>
                    <a href="{{ route('admin.sales.deals.create', ['opportunity_id' => $opportunity->id]) }}" class="btn btn-sm btn-primary" data-lang-en="Add Quotation" data-lang-id="Tambah Quotation">Add Quotation</a>
                </div>

                @if (($recentQuotations ?? collect())->isNotEmpty())
                    <div class="customer-table-wrap">
                        <table class="customer-table sales-table">
                            <thead>
                                <tr>
                                    <th data-lang-en="Quote Number" data-lang-id="Nomor Quotation">Quote Number</th>
                                    <th data-lang-en="Amount" data-lang-id="Nominal">Amount</th>
                                    <th data-lang-en="Status" data-lang-id="Status">Status</th>
                                    <th data-lang-en="Valid Until" data-lang-id="Berlaku Sampai">Valid Until</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentQuotations as $quotation)
                                    <tr>
                                        <td><a href="{{ route('admin.sales.deals.show', $quotation) }}" class="sales-title-link">{{ $quotation->quote_number }}</a></td>
                                        <td>Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</td>
                                        <td><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td>
                                        <td>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p data-lang-en="No recent quotations." data-lang-id="Belum ada quotation terbaru.">No recent quotations.</p>
                @endif
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Recent Activities" data-lang-id="Aktivitas Terbaru">Recent Activities</h3>
                @if ($recentActivities->isNotEmpty())
                    <div class="customer-table-wrap">
                        <table class="customer-table sales-table">
                            <thead>
                                <tr>
                                    <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                                    <th data-lang-en="Subject" data-lang-id="Subjek">Subject</th>
                                    <th data-lang-en="Activity Date" data-lang-id="Tanggal Aktivitas">Activity Date</th>
                                    <th data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentActivities as $activity)
                                    <tr>
                                        <td><span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></td>
                                        <td>{{ $activity->subject }}</td>
                                        <td>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</td>
                                        <td>{{ $activity->assigned_to ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p data-lang-en="No recent activities." data-lang-id="Belum ada aktivitas terbaru.">No recent activities.</p>
                @endif
                <a href="{{ route('admin.sales.activities.create', ['related_type' => 'opportunity', 'related_id' => $opportunity->id]) }}" class="btn btn-sm btn-primary" data-lang-en="Add Activity" data-lang-id="Tambah Aktivitas">Add Activity</a>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Notes" data-lang-id="Catatan">Notes</h3>
                <p>{{ $opportunity->notes ?: '' }}@unless($opportunity->notes)<span data-lang-en="No notes available" data-lang-id="Belum ada catatan">No notes available</span>@endunless</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                <a href="{{ route('admin.sales.opportunities.edit', $opportunity) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
            </div>
        </article>
    </section>
@endsection
