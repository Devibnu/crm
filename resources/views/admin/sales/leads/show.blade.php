@extends('admin.layouts.app')

@section('title', $lead->name.' - Lead - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="{{ $lead->name }} - Lead - Krakatau CRM" data-doc-title-id="{{ $lead->name }} - Lead - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-leads-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Pipeline Intake" data-lang-id="Pipeline Intake">Pipeline Intake</span>
                <h1 data-lang-en="Lead Detail" data-lang-id="Detail Lead">Lead Detail</h1>
                <p data-lang-en="Lead summary for progress monitoring and qualification." data-lang-id="Ringkasan lead untuk monitoring progress dan kualifikasi.">Ringkasan lead untuk monitoring progress dan kualifikasi.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card sales-leads-show-shell">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $lead->name }}</h2>
                    <p>{{ $lead->company_name ?: '' }}@unless($lead->company_name)<span data-lang-en="No company" data-lang-id="Tanpa perusahaan">No company</span>@endunless</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $lead->status }}">{{ ucfirst($lead->status) }}</span>
                    <span class="status-badge priority-{{ $lead->priority }}">{{ ucfirst($lead->priority) }}</span>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <strong>{{ ucfirst($lead->status) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Priority" data-lang-id="Prioritas">Priority</span>
                    <strong>{{ ucfirst($lead->priority) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Source" data-lang-id="Sumber">Source</span>
                    <strong>{{ $lead->source ?: '-' }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Email" data-lang-id="Email">Email</strong><span>{{ $lead->email ?: '-' }}</span></div>
                <div><strong data-lang-en="Phone" data-lang-id="Telepon">Phone</strong><span>{{ $lead->phone ?: '-' }}</span></div>
                <div><strong data-lang-en="Source" data-lang-id="Sumber">Source</strong><span>{{ $lead->source ?: '-' }}</span></div>
                <div><strong data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</strong><span>{{ $lead->assigned_to ?: '-' }}</span></div>
                <div><strong data-lang-en="Created At" data-lang-id="Dibuat Pada">Created At</strong><span>{{ $lead->created_at?->format('d M Y H:i') }}</span></div>
                <div><strong data-lang-en="Updated At" data-lang-id="Diperbarui Pada">Updated At</strong><span>{{ $lead->updated_at?->format('d M Y H:i') }}</span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Notes" data-lang-id="Catatan">Notes</h3>
                <p>{{ $lead->notes ?: '' }}@unless($lead->notes)<span data-lang-en="No notes available" data-lang-id="Belum ada catatan">No notes available</span>@endunless</p>
            </div>

            @if ($lead->customer)
                <div class="customer-notes">
                    <h3 data-lang-en="Related Customer" data-lang-id="Customer Terkait">Related Customer</h3>
                    <p><a href="{{ route('admin.customers.show', $lead->customer) }}" class="btn btn-sm btn-muted">{{ $lead->customer->name }}</a></p>
                </div>
            @endif

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
                <a href="{{ route('admin.sales.activities.create', ['related_type' => 'lead', 'related_id' => $lead->id]) }}" class="btn btn-sm btn-primary" data-lang-en="Add Activity" data-lang-id="Tambah Aktivitas">Add Activity</a>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.sales.leads') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                <a href="{{ route('admin.sales.leads.edit', $lead) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
            </div>
        </article>
    </section>
@endsection
