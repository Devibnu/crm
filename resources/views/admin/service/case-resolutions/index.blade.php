@extends('admin.layouts.app')

@section('title', 'Case Resolution - Krakatau CRM')

@section('content')
    @php
        $hasFilters = $search || $selectedResolutionType || $selectedResolutionOutcome || $selectedRootCause || $selectedKnowledgeCandidate || $selectedCustomerNotified || $selectedDateFrom || $selectedDateTo;
        $exportQuery = array_merge(request()->query(), ['export' => 'csv']);
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">
                    @include('admin.partials.sidebar-icon', ['icon' => 'case'])
                </div>
                <div>
                    <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                    <h1>Case Resolution</h1>
                    <div class="customer-profile-hero-meta" aria-label="Case resolution summary">
                        <span>{{ number_format($summary['total']) }} total resolutions</span>
                        <span>{{ number_format($summary['knowledge_candidate']) }} knowledge candidates</span>
                        <span>{{ number_format($summary['average_resolution_time']) }} min avg resolution</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                <a href="{{ route('admin.service.case-resolutions.index', $exportQuery) }}" class="btn btn-muted">Export CSV</a>
                @can('cases.create')
                    <a href="{{ route('admin.service.case-resolutions.create') }}" class="btn lead-banner-cta">Add Resolution</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Resolution</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>All documented case outcomes</small>
            </article>
            <article class="card sales-summary-card">
                <span>Resolved</span>
                <strong>{{ number_format($summary['resolved']) }}</strong>
                <small>Completed with resolution</small>
            </article>
            <article class="card sales-summary-card">
                <span>Escalated</span>
                <strong>{{ number_format($summary['escalated']) }}</strong>
                <small>Moved to specialist handling</small>
            </article>
            <article class="card sales-summary-card">
                <span>Workaround</span>
                <strong>{{ number_format($summary['workaround']) }}</strong>
                <small>Temporary solution applied</small>
            </article>
            <article class="card sales-summary-card">
                <span>Knowledge Candidate</span>
                <strong>{{ number_format($summary['knowledge_candidate']) }}</strong>
                <small>Reusable support content</small>
            </article>
            <article class="card sales-summary-card">
                <span>Avg Resolution Time</span>
                <strong>{{ number_format($summary['average_resolution_time']) }}</strong>
                <small>Minutes from ticket creation</small>
            </article>
            <article class="card sales-summary-card">
                <span>Avg Reopen Count</span>
                <strong>{{ number_format($summary['average_reopen_count'], 1) }}</strong>
                <small>Reopen signal per resolution</small>
            </article>
        </div>

        <section class="customer-360-dashboard-grid" aria-label="Case resolution analytics">
            @foreach ([
                'Top Resolution Types' => $analytics['types'],
                'Top Root Causes' => $analytics['root_causes'],
                'Top Outcomes' => $analytics['outcomes'],
                'Most Reopened Categories' => $analytics['reopened_categories'],
            ] as $title => $items)
                <article class="customer-profile-latest-card customer-360-section">
                    <div class="customer-profile-section-head">
                        <div>
                            <span>Analytics</span>
                            <h2>{{ $title }}</h2>
                        </div>
                    </div>
                    <div class="customer-profile-latest-list customer-360-sales-summary">
                        @forelse ($items as $label => $count)
                            <div>
                                <span>{{ ucfirst(str_replace('_', ' ', $label ?: 'unknown')) }}</span>
                                <strong>{{ number_format((float) $count) }}</strong>
                                <small>{{ $title === 'Most Reopened Categories' ? 'Total reopens' : 'Resolution records' }}</small>
                            </div>
                        @empty
                            <div>
                                <span>No data</span>
                                <strong>-</strong>
                                <small>Analytics will appear after resolutions are recorded.</small>
                            </div>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Resolution Workspace</h2>
                    <p>Search ticket, subject, customer, resolver, summary, root cause, or outcome.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.case-resolutions.index') }}" class="case-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Ticket, customer, resolver, root cause">
                </label>
                <label class="field">
                    <span>Resolution Type</span>
                    <select name="resolution_type">
                        <option value="">All types</option>
                        @foreach ($resolutionTypeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedResolutionType === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Outcome</span>
                    <select name="resolution_outcome">
                        <option value="">All outcomes</option>
                        @foreach ($resolutionOutcomeOptions as $outcome)
                            <option value="{{ $outcome }}" @selected($selectedResolutionOutcome === $outcome)>{{ ucfirst(str_replace('_', ' ', $outcome)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Root Cause</span>
                    <select name="root_cause">
                        <option value="">All root causes</option>
                        @foreach ($rootCauseOptions as $rootCause)
                            <option value="{{ $rootCause }}" @selected($selectedRootCause === $rootCause)>{{ ucfirst(str_replace('_', ' ', $rootCause)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Knowledge Candidate</span>
                    <select name="knowledge_candidate">
                        <option value="">All</option>
                        @foreach ($knowledgeCandidateOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedKnowledgeCandidate === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Customer Notified</span>
                    <select name="customer_notified">
                        <option value="">All</option>
                        @foreach ($customerNotifiedOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCustomerNotified === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>From</span>
                    <input type="date" name="date_from" value="{{ $selectedDateFrom }}">
                </label>
                <label class="field">
                    <span>To</span>
                    <input type="date" name="date_to" value="{{ $selectedDateTo }}">
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($hasFilters)
                        <a href="{{ route('admin.service.case-resolutions.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Customer</th>
                            <th>Summary</th>
                            <th>Root Cause</th>
                            <th>Outcome</th>
                            <th>Resolver</th>
                            <th>Knowledge</th>
                            <th>Resolved</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resolutions as $resolution)
                            <tr>
                                <td>
                                    <strong class="sales-code">{{ $resolution->ticket?->ticket_number ?: '-' }}</strong>
                                    <small>{{ $resolution->ticket?->subject ?: '-' }}</small>
                                </td>
                                <td>
                                    <strong>{{ $resolution->ticket?->customer?->name ?: '-' }}</strong>
                                    <small>{{ $resolution->ticket?->customer?->company_name ?: 'No company' }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.service.case-resolutions.show', $resolution) }}" class="sales-title-link">{{ $resolution->resolution_summary }}</a>
                                    <small>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</small>
                                </td>
                                <td><span class="status-badge status-pending">{{ ucfirst(str_replace('_', ' ', $resolution->root_cause ?: 'unknown')) }}</span></td>
                                <td><span class="status-badge resolution-{{ $resolution->resolution_type }}">{{ ucfirst(str_replace('_', ' ', $resolution->resolution_outcome ?: 'resolved')) }}</span></td>
                                <td>{{ $resolution->resolved_by ?: '-' }}</td>
                                <td><span class="status-badge status-{{ $resolution->knowledge_candidate ? 'active' : 'inactive' }}">{{ $resolution->knowledge_candidate ? 'Candidate' : 'No' }}</span></td>
                                <td>{{ $resolution->resolved_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        @can('cases.view')
                                            <a href="{{ route('admin.service.case-resolutions.show', $resolution) }}" class="btn btn-sm btn-muted">View</a>
                                        @endcan
                                        @can('cases.update')
                                            <a href="{{ route('admin.service.case-resolutions.edit', $resolution) }}" class="btn btn-sm btn-primary">Edit</a>
                                        @endcan
                                        @can('cases.delete')
                                            <form method="POST" action="{{ route('admin.service.case-resolutions.destroy', $resolution) }}" onsubmit="return confirm('Delete case resolution ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>No case resolutions found</strong>
                                        <span>Document how tickets were solved and turn repeat fixes into reusable knowledge.</span>
                                        @can('cases.create')
                                            <a href="{{ route('admin.service.case-resolutions.create') }}" class="btn btn-primary">Add Resolution</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($resolutions->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $resolutions->firstItem() }}-{{ $resolutions->lastItem() }} dari {{ $resolutions->total() }} resolution
                    </div>
                    <div class="pagination-links">
                        @if ($resolutions->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $resolutions->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($resolutions->getUrlRange(max(1, $resolutions->currentPage() - 2), min($resolutions->lastPage(), $resolutions->currentPage() + 2)) as $page => $url)
                            @if ($page === $resolutions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($resolutions->hasMorePages())
                            <a href="{{ $resolutions->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
