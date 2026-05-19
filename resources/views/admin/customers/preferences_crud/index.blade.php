@extends('admin.layouts.app')

@section('title', 'Preferences - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Preferences - Krakatau CRM" data-doc-title-id="Preferensi - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1 data-lang-en="Preferences" data-lang-id="Preferensi">Preferences</h1>
                <p data-lang-en="Customer preferences such as communication channels, product interest, consent, and segmentation." data-lang-id="Preferensi customer seperti channel komunikasi, minat produk, consent, dan segmentasi.">Preferensi customer seperti channel komunikasi, minat produk, consent, dan segmentasi.</p>
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
                        data-placeholder-en="Search customer, product interest, segment"
                        data-placeholder-id="Cari customer, product interest, segment"
                        data-title-en="Search preference"
                        data-title-id="Cari preferensi"
                    >
                    <select name="preferred_channel" aria-label="Filter preferred channel" data-title-en="Filter preferred channel" data-title-id="Filter channel pilihan">
                        <option value="" data-lang-en="All channels" data-lang-id="Semua channel">Semua channel</option>
                        @foreach ($preferredChannelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedPreferredChannel === $channel)>{{ ucfirst($channel) }}</option>
                        @endforeach
                    </select>
                    <select name="communication_consent" aria-label="Filter communication consent" data-title-en="Filter communication consent" data-title-id="Filter consent komunikasi">
                        <option value="" data-lang-en="All consent" data-lang-id="Semua consent">Semua consent</option>
                        <option value="1" @selected($selectedConsent === '1') data-lang-en="Yes" data-lang-id="Ya">Yes</option>
                        <option value="0" @selected($selectedConsent === '0') data-lang-en="No" data-lang-id="Tidak">No</option>
                    </select>
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($search || $selectedPreferredChannel !== '' || $selectedConsent !== '')
                        <a href="{{ route('admin.customers.preferences') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </form>

                @if ($firstCustomerId)
                    <a href="{{ route('admin.customers.preferences.create', ['customer' => $firstCustomerId]) }}" class="btn btn-primary" data-lang-en="Add Preference" data-lang-id="Tambah Preferensi">Add Preference</a>
                @else
                    <span class="btn btn-disabled" data-lang-en="Add Preference" data-lang-id="Tambah Preferensi">Add Preference</span>
                @endif
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Customer" data-lang-id="Customer">Customer</th>
                            <th data-lang-en="Preferred Channel" data-lang-id="Channel Pilihan">Preferred Channel</th>
                            <th data-lang-en="Product Interest" data-lang-id="Minat Produk">Product Interest</th>
                            <th data-lang-en="Consent" data-lang-id="Consent">Consent</th>
                            <th data-lang-en="Segment" data-lang-id="Segmen">Segment</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                        <span class="status-badge status-active" data-lang-en="Yes" data-lang-id="Ya">Yes</span>
                                    @else
                                        <span class="status-badge status-inactive" data-lang-en="No" data-lang-id="Tidak">No</span>
                                    @endif
                                </td>
                                <td>{{ $preference->segment ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.customers.preferences.edit', $preference) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.preferences.destroy', $preference) }}" data-confirm-en="Delete this preference?" data-confirm-id="Hapus preferensi ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus preferensi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="customer-empty" data-lang-en="No preferences yet." data-lang-id="Belum ada preferensi.">Belum ada preferensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($preferences->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $preferences->firstItem() }}-{{ $preferences->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $preferences->total() }} <span data-lang-en="preferences" data-lang-id="preferensi">preferences</span>
                    </div>
                    <div class="pagination-links">
                        @if ($preferences->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $preferences->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($preferences->getUrlRange(max(1, $preferences->currentPage() - 2), min($preferences->lastPage(), $preferences->currentPage() + 2)) as $page => $url)
                            @if ($page === $preferences->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($preferences->hasMorePages())
                            <a href="{{ $preferences->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
