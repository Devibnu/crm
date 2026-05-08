@extends('admin.layouts.app')

@section('title', $opportunity->title.' - Opportunity - Krakatau CRM')

@section('content')
    @php
        $probability = min(max((int) $opportunity->probability, 0), 100);
    @endphp

    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'opportunity'])
            </div>
            <div>
                <h1>Opportunity Management</h1>
                <p>Kelola peluang bisnis dan proses discovery.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $opportunity->title }}</h2>
                    <p>{{ $opportunity->company_name ?: 'No company name' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span>
                </div>
            </div>

            <div class="customer-show-grid">
                <div><strong>Company</strong><span>{{ $opportunity->company_name ?: '-' }}</span></div>
                <div><strong>Contact</strong><span>{{ $opportunity->contact_name ?: '-' }}</span></div>
                <div><strong>Estimated Value</strong><span>Rp {{ number_format((float) $opportunity->estimated_value, 2, ',', '.') }}</span></div>
                <div><strong>Expected Close</strong><span>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</span></div>
                <div><strong>Assigned To</strong><span>{{ $opportunity->assigned_to ?: '-' }}</span></div>
                <div><strong>Created At</strong><span>{{ $opportunity->created_at?->format('d M Y H:i') }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Probability</h3>
                <div style="display:grid;gap:8px;max-width:420px;">
                    <span style="font-size:13px;color:#6f6b7d;font-weight:600;">{{ $probability }}%</span>
                    <div style="height:10px;background:#ece9ff;border-radius:999px;overflow:hidden;">
                        <span style="display:block;height:100%;width:{{ $probability }}%;background:linear-gradient(90deg,#7367f0,#8f84ff);"></span>
                    </div>
                </div>
            </div>

            @if ($opportunity->lead)
                <div class="customer-notes">
                    <h3>Related Lead</h3>
                    <p><a href="{{ route('admin.sales.leads.show', $opportunity->lead) }}" class="btn btn-sm btn-muted">{{ $opportunity->lead->name }}</a></p>
                </div>
            @endif

            @if ($opportunity->customer)
                <div class="customer-notes">
                    <h3>Related Customer</h3>
                    <p><a href="{{ route('admin.customers.show', $opportunity->customer) }}" class="btn btn-sm btn-muted">{{ $opportunity->customer->name }}</a></p>
                </div>
            @endif

            <div class="customer-notes">
                <div class="sales-section-head">
                    <div>
                        <h3>Recent Quotations</h3>
                    </div>
                    <a href="{{ route('admin.sales.deals.create', ['opportunity_id' => $opportunity->id]) }}" class="btn btn-sm btn-primary">Add Quotation</a>
                </div>

                @if (($recentQuotations ?? collect())->isNotEmpty())
                    <div class="customer-table-wrap">
                        <table class="customer-table sales-table">
                            <thead>
                                <tr>
                                    <th>Quote Number</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Valid Until</th>
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
                    <p>No recent quotations.</p>
                @endif
            </div>

            <div class="customer-notes">
                <h3>Recent Activities</h3>
                @if ($recentActivities->isNotEmpty())
                    <div class="customer-table-wrap">
                        <table class="customer-table sales-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Activity Date</th>
                                    <th>Assigned To</th>
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
                    <p>No recent activities.</p>
                @endif
                <a href="{{ route('admin.sales.activities.create', ['related_type' => 'opportunity', 'related_id' => $opportunity->id]) }}" class="btn btn-sm btn-primary">Add Activity</a>
            </div>

            <div class="customer-notes">
                <h3>Notes</h3>
                <p>{{ $opportunity->notes ?: 'No notes available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-muted">Back</a>
                <a href="{{ route('admin.sales.opportunities.edit', $opportunity) }}" class="btn btn-primary">Edit</a>
            </div>
        </article>
    </section>
@endsection
