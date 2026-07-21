@extends('admin.layouts.app')

@section('title', 'Behavior - Krakatau CRM')

@section('content')
    @php
        $visibleBehaviors = $behaviors->getCollection();
        $latestBehavior = $visibleBehaviors->first();
        $averageVisibleScore = $visibleBehaviors->avg('engagement_score') ?? 0;
        $selectedLifecycleLabel = $selectedLifecycleStage ? ucfirst($selectedLifecycleStage) : 'All Lifecycle';
    @endphp

    <section class="lead-list-page customer-behavior-list-page">
        <header class="lead-list-header lead-form-banner customer-form-hero customer-interaction-list-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PROFILE 360</span>
                <h1>Behavior</h1>
                <p>Track customer lifecycle, engagement score, product interest, and latest activity signals.</p>
                <div class="customer-form-hero-meta">
                    <span>{{ $selectedLifecycleLabel }}</span>
                    @if ($search)
                        <span>Search: {{ $search }}</span>
                    @endif
                </div>
            </div>
            <div class="customer-interaction-hero-summary" aria-label="Behavior quick summary">
                <div>
                    <span>Total Behavior</span>
                    <strong>{{ number_format($behaviors->total()) }}</strong>
                </div>
                <div>
                    <span>Latest Stage</span>
                    <strong>{{ $latestBehavior?->lifecycle_stage ? ucfirst($latestBehavior->lifecycle_stage) : '-' }}</strong>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-interaction-kpi-strip customer-behavior-kpi-strip" aria-label="Behavior summary">
            <div>
                <strong>{{ number_format($behaviors->total()) }}</strong>
                <span>Behavior Records</span>
            </div>
            <div>
                <strong>{{ number_format($visibleBehaviors->where('lifecycle_stage', 'active')->count()) }}</strong>
                <span>Active</span>
            </div>
            <div>
                <strong>{{ number_format($visibleBehaviors->where('lifecycle_stage', 'loyal')->count()) }}</strong>
                <span>Loyal</span>
            </div>
            <div>
                <strong>{{ number_format($averageVisibleScore, 1, ',', '.') }}</strong>
                <span>Visible Avg Score</span>
            </div>
        </div>

        <article class="card customer-table-card customer-interaction-table-card customer-behavior-table-card">
            <div class="customer-table-toolbar lead-list-toolbar customer-interaction-toolbar">
                <form method="GET" action="{{ route('admin.customers.behavior') }}" class="customer-search-form lead-smart-filters customer-interaction-filters">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search behavior..."
                        aria-label="Search behavior"
                    >
                    <select name="lifecycle_stage" aria-label="Filter lifecycle stage">
                        <option value="">All Lifecycle</option>
                        @foreach ($lifecycleStageOptions as $stage)
                            <option value="{{ $stage }}" @selected($selectedLifecycleStage === $stage)>{{ ucfirst($stage) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Apply</button>
                    @if ($search || $selectedLifecycleStage)
                        <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                @can('customers.create')
                    @if ($customers->isNotEmpty())
                        <button type="button" class="btn btn-primary" data-customer-selector-trigger="newBehavior">New Behavior</button>
                    @else
                        <span class="btn btn-disabled">New Behavior</span>
                    @endif
                @endcan
            </div>

            <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                <table class="customer-table lead-modern-table customer-interaction-table customer-behavior-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Lifecycle Stage</th>
                            <th>Engagement Score</th>
                            <th>Last Activity</th>
                            <th>Product Interest</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($behaviors as $behavior)
                            <tr>
                                <td>
                                    <strong>{{ $behavior->customer?->name ?: '-' }}</strong>
                                </td>
                                <td><span class="status-badge status-new">{{ ucfirst($behavior->lifecycle_stage) }}</span></td>
                                <td>
                                    <strong>{{ $behavior->engagement_score }}</strong><small>/100 engagement</small>
                                </td>
                                <td>{{ $behavior->last_activity_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <strong>{{ $behavior->product_interest ?: '-' }}</strong>
                                    <small>{{ \Illuminate\Support\Str::limit($behavior->behavior_notes ?: '-', 70) }}</small>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        @can('customers.update')
                                            <a href="{{ route('admin.customers.behavior.edit', $behavior) }}" class="btn btn-sm btn-primary">Edit</a>
                                        @endcan
                                        @can('customers.delete')
                                            <form method="POST" action="{{ route('admin.customers.behavior.destroy', $behavior) }}" onsubmit="return confirm('Delete behavior ini?');">
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
                                <td colspan="6">
                                    <div class="customer-profile-enterprise-empty customer-interaction-empty customer-behavior-empty">
                                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span>
                                        <strong>No Behavior Yet</strong>
                                        <p>Customer lifecycle, engagement, and activity signals will appear here.</p>
                                        @can('customers.create')
                                            @if ($customers->isNotEmpty())
                                                <button type="button" class="btn btn-primary" data-customer-selector-trigger="newBehavior">New Behavior</button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($behaviors->hasPages())
                <div class="customer-pagination lead-pagination">
                    <div class="pagination-info">
                        Showing {{ $behaviors->firstItem() }}-{{ $behaviors->lastItem() }} of {{ $behaviors->total() }} behavior records
                    </div>
                    <div class="pagination-links">
                        @if ($behaviors->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $behaviors->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($behaviors->getUrlRange(max(1, $behaviors->currentPage() - 2), min($behaviors->lastPage(), $behaviors->currentPage() + 2)) as $page => $url)
                            @if ($page === $behaviors->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($behaviors->hasMorePages())
                            <a href="{{ $behaviors->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>

        @can('customers.create')
            <x-crm.customer-selector-modal
                modal-id="newBehavior"
                title="New Behavior"
                description="Select a customer before creating a behavior record."
                :customers="$customers"
                route-name="admin.customers.behavior.create"
                empty-message="No customers available for behavior records."
            />
        @endcan
    </section>
@endsection
