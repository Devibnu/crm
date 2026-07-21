@extends('admin.layouts.app')

@section('title', 'Preferences - Krakatau CRM')

@section('content')
    @php
        $visiblePreferences = $preferences->getCollection();
        $latestPreference = $visiblePreferences->first();
        $consentedCount = $visiblePreferences->where('communication_consent', true)->count();
        $selectedChannelLabel = $selectedPreferredChannel ? ucfirst($selectedPreferredChannel) : 'All Channels';
        $selectedConsentLabel = match ($selectedConsent) {
            '1' => 'Consent: Yes',
            '0' => 'Consent: No',
            default => 'All Consent',
        };
        $customerSelectorCustomers = \App\Models\Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'company_name', 'email', 'phone']);
    @endphp

    <section class="lead-list-page customer-preference-list-page">
        <header class="lead-list-header lead-form-banner customer-form-hero customer-interaction-list-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PROFILE 360</span>
                <h1>Preferences</h1>
                <p>Manage customer communication channels, product interests, consent, and segmentation preferences.</p>
                <div class="customer-form-hero-meta">
                    <span>{{ $selectedChannelLabel }}</span>
                    <span>{{ $selectedConsentLabel }}</span>
                    @if ($search)
                        <span>Search: {{ $search }}</span>
                    @endif
                </div>
            </div>
            <div class="customer-interaction-hero-summary" aria-label="Preference quick summary">
                <div>
                    <span>Total Preferences</span>
                    <strong>{{ number_format($preferences->total()) }}</strong>
                </div>
                <div>
                    <span>Latest Channel</span>
                    <strong>{{ $latestPreference?->preferred_channel ? ucfirst($latestPreference->preferred_channel) : '-' }}</strong>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-interaction-kpi-strip customer-preference-kpi-strip" aria-label="Preference summary">
            <div>
                <strong>{{ number_format($preferences->total()) }}</strong>
                <span>Preferences</span>
            </div>
            <div>
                <strong>{{ number_format($consentedCount) }}</strong>
                <span>Consent Yes</span>
            </div>
            <div>
                <strong>{{ number_format($visiblePreferences->where('preferred_channel', 'whatsapp')->count()) }}</strong>
                <span>WhatsApp</span>
            </div>
            <div>
                <strong>{{ number_format($visiblePreferences->where('preferred_channel', 'email')->count()) }}</strong>
                <span>Email</span>
            </div>
        </div>

        <article class="card customer-table-card customer-interaction-table-card customer-preference-table-card">
            <div class="customer-table-toolbar lead-list-toolbar customer-interaction-toolbar">
                <form method="GET" action="{{ route('admin.customers.preferences') }}" class="customer-search-form lead-smart-filters customer-interaction-filters">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search preferences..."
                        aria-label="Search preference"
                    >
                    <select name="preferred_channel" aria-label="Filter preferred channel">
                        <option value="">All Channels</option>
                        @foreach ($preferredChannelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedPreferredChannel === $channel)>{{ ucfirst($channel) }}</option>
                        @endforeach
                    </select>
                    <select name="communication_consent" aria-label="Filter communication consent">
                        <option value="">All Consent</option>
                        <option value="1" @selected($selectedConsent === '1')>Yes</option>
                        <option value="0" @selected($selectedConsent === '0')>No</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Apply</button>
                    @if ($search || $selectedPreferredChannel !== '' || $selectedConsent !== '')
                        <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                @can('customers.create')
                    @if ($customerSelectorCustomers->isNotEmpty())
                        <button type="button" class="btn btn-primary" data-customer-selector-trigger="newPreference">New Preference</button>
                    @else
                        <span class="btn btn-disabled">New Preference</span>
                    @endif
                @endcan
            </div>

            <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                <table class="customer-table lead-modern-table customer-interaction-table customer-preference-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Preferred Channel</th>
                            <th>Product Interest</th>
                            <th>Consent</th>
                            <th>Segment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preferences as $preference)
                            <tr>
                                <td>
                                    <strong>{{ $preference->customer?->name ?: '-' }}</strong>
                                </td>
                                <td>
                                    <span class="status-badge status-pending">{{ ucfirst($preference->preferred_channel) }}</span>
                                </td>
                                <td>
                                    <strong>{{ $preference->product_interest ?: '-' }}</strong>
                                    <small>{{ \Illuminate\Support\Str::limit($preference->notes ?: '-', 70) }}</small>
                                </td>
                                <td>
                                    @if ($preference->communication_consent)
                                        <span class="status-badge status-active">Yes</span>
                                    @else
                                        <span class="status-badge status-inactive">No</span>
                                    @endif
                                </td>
                                <td>{{ $preference->segment ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        @can('customers.update')
                                            <a href="{{ route('admin.customers.preferences.edit', $preference) }}" class="btn btn-sm btn-primary">Edit</a>
                                        @endcan
                                        @can('customers.delete')
                                            <form method="POST" action="{{ route('admin.customers.preferences.destroy', $preference) }}" onsubmit="return confirm('Delete preference ini?');">
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
                                    <div class="customer-profile-enterprise-empty customer-interaction-empty customer-preference-empty">
                                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'lock'])</span>
                                        <strong>No Preferences Yet</strong>
                                        <p>Customer communication preferences and segmentation details will appear here.</p>
                                        @can('customers.create')
                                            @if ($customerSelectorCustomers->isNotEmpty())
                                                <button type="button" class="btn btn-primary" data-customer-selector-trigger="newPreference">New Preference</button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($preferences->hasPages())
                <div class="customer-pagination lead-pagination">
                    <div class="pagination-info">
                        Showing {{ $preferences->firstItem() }}-{{ $preferences->lastItem() }} of {{ $preferences->total() }} preferences
                    </div>
                    <div class="pagination-links">
                        @if ($preferences->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $preferences->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($preferences->getUrlRange(max(1, $preferences->currentPage() - 2), min($preferences->lastPage(), $preferences->currentPage() + 2)) as $page => $url)
                            @if ($page === $preferences->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($preferences->hasMorePages())
                            <a href="{{ $preferences->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>

        @can('customers.create')
            <x-crm.customer-selector-modal
                modal-id="newPreference"
                title="New Preference"
                description="Select a customer before creating a preference record."
                :customers="$customerSelectorCustomers"
                route-name="admin.customers.preferences.create"
                empty-message="No customers available for preference records."
            />
        @endcan
    </section>
@endsection
