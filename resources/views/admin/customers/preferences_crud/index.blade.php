@extends('admin.layouts.app')

@section('title', 'Preferences - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1>Preferences</h1>
                <p>Preferensi customer seperti channel komunikasi, minat produk, consent, dan segmentasi.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <form method="GET" action="{{ route('admin.customers.preferences') }}" class="customer-search-form">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari customer, product interest, segment"
                        aria-label="Search preference"
                    >
                    <select name="preferred_channel" aria-label="Filter preferred channel">
                        <option value="">Semua channel</option>
                        @foreach ($preferredChannelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedPreferredChannel === $channel)>{{ ucfirst($channel) }}</option>
                        @endforeach
                    </select>
                    <select name="communication_consent" aria-label="Filter communication consent">
                        <option value="">Semua consent</option>
                        <option value="1" @selected($selectedConsent === '1')>Yes</option>
                        <option value="0" @selected($selectedConsent === '0')>No</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedPreferredChannel !== '' || $selectedConsent !== '')
                        <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                @if ($firstCustomerId)
                    <a href="{{ route('admin.customers.preferences.create', ['customer' => $firstCustomerId]) }}" class="btn btn-primary">Add Preference</a>
                @else
                    <span class="btn btn-disabled">Add Preference</span>
                @endif
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
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
                                <td>{{ $preference->customer?->name ?: '-' }}</td>
                                <td>{{ ucfirst($preference->preferred_channel) }}</td>
                                <td>{{ $preference->product_interest ?: '-' }}</td>
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
                                        <a href="{{ route('admin.customers.preferences.edit', $preference) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.preferences.destroy', $preference) }}" onsubmit="return confirm('Delete preference ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="customer-empty">Belum ada preference.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($preferences->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $preferences->firstItem() }}-{{ $preferences->lastItem() }} dari {{ $preferences->total() }} preference
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
    </section>
@endsection
