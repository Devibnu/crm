@extends('admin.layouts.app')

@section('title', 'Lead Scoring & Routing - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'scoring'])
            </div>
            <div>
                <h1>Lead Scoring & Routing</h1>
                <p>Kelola scoring lead dan rule distribusi otomatis ke sales team.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Rules</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua scoring rule</small>
            </article>
            <article class="card sales-summary-card">
                <span>Active Rules</span>
                <strong>{{ number_format($summary['active']) }}</strong>
                <small>Rule aktif</small>
            </article>
            <article class="card sales-summary-card">
                <span>Auto Assign Rules</span>
                <strong>{{ number_format($summary['auto_assign']) }}</strong>
                <small>Routing otomatis</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Executions</span>
                <strong>{{ number_format($summary['executions']) }}</strong>
                <small>Total eksekusi scoring</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Lead Scoring Rules</h2>
                    <p>Search name atau notes, lalu filter trigger source, priority, dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.lead-scoring.create') }}" class="btn btn-primary">Add Rule</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.lead-scoring.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or notes" aria-label="Search lead scoring rules">
                </label>
                <label class="field">
                    <span>Trigger Source</span>
                    <select name="trigger_source">
                        <option value="">All triggers</option>
                        @foreach ($triggerOptions as $trigger)
                            <option value="{{ $trigger }}" @selected($selectedTrigger === $trigger)>{{ ucwords(str_replace('_', ' ', $trigger)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Priority</span>
                    <select name="priority">
                        <option value="">All priorities</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
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
                    @if ($search || $selectedTrigger || $selectedPriority || $selectedStatus)
                        <a href="{{ route('admin.marketing.lead-scoring.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Rule Name</th>
                            <th>Trigger Source</th>
                            <th>Score</th>
                            <th>Routing Team</th>
                            <th>Routing User</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Auto Assign</th>
                            <th>Execution Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            <tr>
                                <td><a href="{{ route('admin.marketing.lead-scoring.show', $rule) }}" class="sales-title-link">{{ $rule->name }}</a></td>
                                <td><span class="status-badge trigger-{{ $rule->trigger_source }}">{{ ucwords(str_replace('_', ' ', $rule->trigger_source)) }}</span></td>
                                <td>{{ number_format($rule->score_value) }}</td>
                                <td>{{ $rule->routing_team ?: '-' }}</td>
                                <td>{{ $rule->routing_user ?: '-' }}</td>
                                <td><span class="status-badge priority-{{ $rule->priority }}">{{ ucfirst($rule->priority) }}</span></td>
                                <td><span class="status-badge status-{{ $rule->status }}">{{ ucfirst($rule->status) }}</span></td>
                                <td><span class="status-badge status-{{ $rule->auto_assign ? 'active' : 'inactive' }}">{{ $rule->auto_assign ? 'Yes' : 'No' }}</span></td>
                                <td>{{ number_format($rule->execution_count) }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.lead-scoring.show', $rule) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.marketing.lead-scoring.edit', $rule) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.lead-scoring.destroy', $rule) }}" onsubmit="return confirm('Delete lead scoring rule ini?');">
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
                                        <strong>Belum ada scoring rule</strong>
                                        <span>Tambahkan rule pertama untuk mulai scoring dan routing lead.</span>
                                        <a href="{{ route('admin.marketing.lead-scoring.create') }}" class="btn btn-primary">Add Rule</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rules->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $rules->firstItem() }}-{{ $rules->lastItem() }} dari {{ $rules->total() }} rule
                    </div>
                    <div class="pagination-links">
                        @if ($rules->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $rules->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($rules->getUrlRange(max(1, $rules->currentPage() - 2), min($rules->lastPage(), $rules->currentPage() + 2)) as $page => $url)
                            @if ($page === $rules->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($rules->hasMorePages())
                            <a href="{{ $rules->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
