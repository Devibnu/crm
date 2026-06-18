@extends('admin.layouts.app')

@section('title', $lead->name.' - Lead - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <h1>Lead Detail</h1>
                <p>Ringkasan lead untuk monitoring progress dan kualifikasi.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $lead->name }}</h2>
                    <p>{{ $lead->company_name ?: 'No company' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge lead-score-badge">Score {{ (int) $lead->lead_score }}</span>
                    <span class="status-badge lead-temperature-{{ $lead->lead_temperature ?: 'cold' }}">{{ ucfirst($lead->lead_temperature ?: 'cold') }}</span>
                    <span class="status-badge status-{{ $lead->status }}">{{ ucfirst($lead->status) }}</span>
                    <span class="status-badge priority-{{ $lead->priority }}">{{ ucfirst($lead->priority) }}</span>
                </div>
            </div>

            <div class="customer-show-grid">
                <div><strong>Email</strong><span>{{ $lead->email ?: '-' }}</span></div>
                <div><strong>Phone</strong><span>{{ $lead->phone ?: '-' }}</span></div>
                <div><strong>Source</strong><span>{{ $lead->source ?: '-' }}</span></div>
                @if ($lead->lead_source === 'whatsapp')
                    <div><strong>Lead Source</strong><span><span class="status-badge source-whatsapp">WhatsApp</span></span></div>
                @endif
                <div><strong>Assigned To</strong><span>{{ $lead->assigned_to ?: '-' }}</span></div>
                <div><strong>Source Campaign</strong><span>{{ $lead->source_campaign ?: '-' }}</span></div>
                <div>
                    <strong>Source WhatsApp Conversation</strong>
                    <span>
                        @if ($lead->sourceWhatsappConversation)
                            <a href="{{ url('/admin/service/omnichannel?conversation='.$lead->sourceWhatsappConversation->id) }}" class="btn btn-sm btn-muted">
                                {{ $lead->sourceWhatsappConversation->contact_name ?: $lead->sourceWhatsappConversation->phone_number }}
                            </a>
                        @else
                            -
                        @endif
                    </span>
                </div>
                <div><strong>Created At</strong><span>{{ $lead->created_at?->format('d M Y H:i') }}</span></div>
                <div><strong>Updated At</strong><span>{{ $lead->updated_at?->format('d M Y H:i') }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Score Breakdown</h3>
                @if (filled($lead->lead_score_breakdown))
                    <div class="lead-score-breakdown">
                        @foreach ($lead->lead_score_breakdown as $item)
                            <div>
                                <span>{{ $item['label'] ?? '-' }}</span>
                                <strong>+{{ (int) ($item['points'] ?? 0) }}</strong>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p>No score activity yet.</p>
                @endif
            </div>

            <div class="customer-notes">
                <h3>Notes</h3>
                <p>{{ $lead->notes ?: 'No notes available' }}</p>
            </div>

            @if ($lead->customer)
                <div class="customer-notes">
                    <h3>Related Customer</h3>
                    <p><a href="{{ route('admin.customers.show', $lead->customer) }}" class="btn btn-sm btn-muted">{{ $lead->customer->name }}</a></p>
                </div>
            @endif

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
                <a href="{{ route('admin.sales.activities.create', ['related_type' => 'lead', 'related_id' => $lead->id]) }}" class="btn btn-sm btn-primary">Add Activity</a>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.sales.leads') }}" class="btn btn-muted">Back</a>
                <a href="{{ route('admin.sales.leads.edit', $lead) }}" class="btn btn-primary">Edit</a>
            </div>
        </article>
    </section>
@endsection
