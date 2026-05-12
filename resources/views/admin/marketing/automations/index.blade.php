@extends('admin.layouts.app')

@section('title', 'Automation & Nurturing - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'automation'])
            </div>
            <div>
                <h1>Automation & Nurturing</h1>
                <p>Kelola rule automation untuk nurturing lead dan follow-up marketing.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Automations</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua rule automation</small>
            </article>
            <article class="card sales-summary-card">
                <span>Active Automations</span>
                <strong>{{ number_format($summary['active']) }}</strong>
                <small>Rule sedang aktif</small>
            </article>
            <article class="card sales-summary-card">
                <span>Paused Automations</span>
                <strong>{{ number_format($summary['paused']) }}</strong>
                <small>Rule ditunda</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Executed</span>
                <strong>{{ number_format($summary['executed']) }}</strong>
                <small>Total eksekusi rule</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Automation Rules</h2>
                    <p>Search name atau notes, lalu filter trigger, action, dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.automations.create') }}" class="btn btn-primary">Add Automation</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.automations.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or notes" aria-label="Search automations">
                </label>
                <label class="field">
                    <span>Trigger</span>
                    <select name="trigger_type">
                        <option value="">All triggers</option>
                        @foreach ($triggerOptions as $trigger)
                            <option value="{{ $trigger }}" @selected($selectedTrigger === $trigger)>{{ ucwords(str_replace('_', ' ', $trigger)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Action</span>
                    <select name="action_type">
                        <option value="">All actions</option>
                        @foreach ($actionOptions as $action)
                            <option value="{{ $action }}" @selected($selectedAction === $action)>{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($search || $selectedTrigger || $selectedAction || $selectedStatus)
                        <a href="{{ route('admin.marketing.automations.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Campaign</th>
                            <th>Audience Segment</th>
                            <th>Trigger</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Delay</th>
                            <th>Executed</th>
                            <th>Last Executed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($automations as $automation)
                            <tr>
                                <td><a href="{{ route('admin.marketing.automations.show', $automation) }}" class="sales-title-link">{{ $automation->name }}</a></td>
                                <td>{{ $automation->marketingCampaign?->name ?: '-' }}</td>
                                <td>{{ $automation->audienceSegment?->name ?: '-' }}</td>
                                <td><span class="status-badge trigger-{{ $automation->trigger_type }}">{{ ucwords(str_replace('_', ' ', $automation->trigger_type)) }}</span></td>
                                <td><span class="status-badge action-{{ $automation->action_type }}">{{ ucwords(str_replace('_', ' ', $automation->action_type)) }}</span></td>
                                <td><span class="status-badge status-{{ $automation->status }}">{{ ucfirst($automation->status) }}</span></td>
                                <td>{{ number_format($automation->delay_minutes) }} min</td>
                                <td>{{ number_format($automation->executed_count) }}</td>
                                <td>{{ $automation->last_executed_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.automations.show', $automation) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.marketing.automations.edit', $automation) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.automations.destroy', $automation) }}" onsubmit="return confirm('Delete automation ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada automation</strong>
                                        <span>Tambahkan rule pertama untuk mulai nurturing lead otomatis.</span>
                                        <a href="{{ route('admin.marketing.automations.create') }}" class="btn btn-primary">Add Automation</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($automations->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $automations->firstItem() }}-{{ $automations->lastItem() }} dari {{ $automations->total() }} automation
                    </div>
                    <div class="pagination-links">
                        @if ($automations->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $automations->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($automations->getUrlRange(max(1, $automations->currentPage() - 2), min($automations->lastPage(), $automations->currentPage() + 2)) as $page => $url)
                            @if ($page === $automations->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($automations->hasMorePages())
                            <a href="{{ $automations->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
