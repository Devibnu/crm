@extends('admin.layouts.app')

@section('title', 'Interaction History - Krakatau CRM')

@section('content')
    @php
        $visibleInteractions = $interactions->getCollection();
        $latestInteraction = $visibleInteractions->first();
        $typeBadgeClass = fn (string $type): string => match ($type) {
            'call' => 'status-active',
            'meeting' => 'status-pending',
            'email' => 'status-new',
            'whatsapp' => 'status-won',
            'follow_up' => 'status-pending',
            default => 'status-inactive',
        };
        $selectedTypeLabel = $selectedType ? ucwords(str_replace('_', ' ', $selectedType)) : 'All Types';
        $customerSelectorCustomers = \App\Models\Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'company_name', 'email', 'phone']);
    @endphp

    <section class="lead-list-page customer-interaction-list-page">
        <header class="lead-list-header lead-form-banner customer-form-hero customer-interaction-list-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PROFILE 360</span>
                <h1>Interaction History</h1>
                <p>Track customer communications, activity notes, and follow-up history across all customer records.</p>
                <div class="customer-form-hero-meta">
                    <span>{{ $selectedTypeLabel }}</span>
                    @if ($search)
                        <span>Search: {{ $search }}</span>
                    @endif
                </div>
            </div>
            <div class="customer-interaction-hero-summary" aria-label="Interaction quick summary">
                <div>
                    <span>Total Interactions</span>
                    <strong>{{ number_format($interactions->total()) }}</strong>
                </div>
                <div>
                    <span>Latest Activity</span>
                    <strong>{{ $latestInteraction?->interaction_at?->format('d M Y') ?: '-' }}</strong>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-interaction-kpi-strip" aria-label="Interaction summary">
            <div>
                <strong>{{ number_format($interactions->total()) }}</strong>
                <span>Interactions</span>
            </div>
            <div>
                <strong>{{ number_format($visibleInteractions->where('type', 'call')->count()) }}</strong>
                <span>Calls</span>
            </div>
            <div>
                <strong>{{ number_format($visibleInteractions->where('type', 'meeting')->count()) }}</strong>
                <span>Meetings</span>
            </div>
            <div>
                <strong>{{ $latestInteraction?->interaction_at?->format('d M') ?: '-' }}</strong>
                <span>Latest Activity</span>
            </div>
        </div>

        <article class="card customer-table-card customer-interaction-table-card">
            <div class="customer-table-toolbar lead-list-toolbar customer-interaction-toolbar">
                <form method="GET" action="{{ route('admin.customers.interactions') }}" class="customer-search-form lead-smart-filters customer-interaction-filters">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search interactions..."
                        aria-label="Search interaction"
                    >
                    <select name="type" aria-label="Filter type">
                        <option value="">All Types</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Apply</button>
                    @if ($search || $selectedType)
                        <a href="{{ route('admin.customers.interactions') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                @can('interactions.create')
                    @if ($customerSelectorCustomers->isNotEmpty())
                        <button type="button" class="btn btn-primary" data-customer-selector-trigger="newInteraction">- New Interaction</button>
                    @else
                        <span class="btn btn-disabled">- New Interaction</span>
                    @endif
                @endcan
            </div>

            <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                <table class="customer-table lead-modern-table customer-interaction-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Handled By</th>
                            <th>Outcome</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($interactions as $interaction)
                            <tr>
                                <td>
                                    <strong>{{ $interaction->customer?->name ?: '-' }}</strong>
                                </td>
                                <td>
                                    <span class="status-badge {{ $typeBadgeClass($interaction->type) }}">{{ ucwords(str_replace('_', ' ', $interaction->type)) }}</span>
                                </td>
                                <td>
                                    <strong>{{ $interaction->subject }}</strong>
                                    <small>{{ \Illuminate\Support\Str::limit($interaction->description ?: '-', 70) }}</small>
                                </td>
                                <td>{{ $interaction->interaction_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $interaction->handled_by ?: '-' }}</td>
                                <td>{{ $interaction->outcome ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        @can('interactions.update')
                                            <a href="{{ route('admin.customers.interactions.edit', $interaction) }}" class="btn btn-sm btn-primary">Edit</a>
                                        @endcan
                                        @can('interactions.delete')
                                            <form method="POST" action="{{ route('admin.customers.interactions.destroy', $interaction) }}" onsubmit="return confirm('Delete interaction ini?');">
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
                                <td colspan="7">
                                    <div class="customer-profile-enterprise-empty customer-interaction-empty">
                                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'mail'])</span>
                                        <strong>No Interactions Yet</strong>
                                        <p>Customer communication history will appear here.</p>
                                        @can('interactions.create')
                                            @if ($customerSelectorCustomers->isNotEmpty())
                                                <button type="button" class="btn btn-primary" data-customer-selector-trigger="newInteraction">- New Interaction</button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($interactions->hasPages())
                <div class="customer-pagination lead-pagination">
                    <div class="pagination-info">
                        Showing {{ $interactions->firstItem() }}-{{ $interactions->lastItem() }} of {{ $interactions->total() }} interactions
                    </div>
                    <div class="pagination-links">
                        @if ($interactions->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $interactions->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($interactions->getUrlRange(max(1, $interactions->currentPage() - 2), min($interactions->lastPage(), $interactions->currentPage() + 2)) as $page => $url)
                            @if ($page === $interactions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($interactions->hasMorePages())
                            <a href="{{ $interactions->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>

        @can('interactions.create')
            <x-crm.customer-selector-modal
                modal-id="newInteraction"
                title="New Interaction"
                description="Select a customer before creating an interaction record."
                :customers="$customerSelectorCustomers"
                route-name="admin.customers.interactions.create"
                empty-message="No customers available for interaction records."
            />
        @endcan
    </section>
@endsection
