@extends('admin.layouts.app')

@section('title', 'Behavior - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Behavior - Krakatau CRM" data-doc-title-id="Perilaku - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1 data-lang-en="Behavior" data-lang-id="Perilaku">Behavior</h1>
                <p data-lang-en="Customer behavior data such as lifecycle stage, engagement, and last activity." data-lang-id="Data perilaku customer seperti lifecycle stage, engagement, dan aktivitas terakhir.">Data perilaku customer seperti lifecycle stage, engagement, dan aktivitas terakhir.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <form method="GET" action="{{ route('admin.customers.behavior') }}" class="customer-search-form">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari customer atau product interest"
                        aria-label="Search behavior"
                        data-placeholder-en="Search customer or product interest"
                        data-placeholder-id="Cari customer atau product interest"
                        data-title-en="Search behavior"
                        data-title-id="Cari perilaku"
                    >
                    <select name="lifecycle_stage" aria-label="Filter lifecycle stage" data-title-en="Filter lifecycle stage" data-title-id="Filter lifecycle stage">
                        <option value="" data-lang-en="All lifecycle stages" data-lang-id="Semua lifecycle stage">Semua lifecycle stage</option>
                        @foreach ($lifecycleStageOptions as $stage)
                            <option value="{{ $stage }}" @selected($selectedLifecycleStage === $stage)>{{ ucfirst($stage) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($search || $selectedLifecycleStage)
                        <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </form>

                @if ($firstCustomerId)
                    <a href="{{ route('admin.customers.behavior.create', ['customer' => $firstCustomerId]) }}" class="btn btn-primary" data-lang-en="Add Behavior" data-lang-id="Tambah Perilaku">Add Behavior</a>
                @else
                    <span class="btn btn-disabled" data-lang-en="Add Behavior" data-lang-id="Tambah Perilaku">Add Behavior</span>
                @endif
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Customer" data-lang-id="Customer">Customer</th>
                            <th data-lang-en="Lifecycle Stage" data-lang-id="Lifecycle Stage">Lifecycle Stage</th>
                            <th data-lang-en="Engagement Score" data-lang-id="Skor Engagement">Engagement Score</th>
                            <th data-lang-en="Last Activity" data-lang-id="Aktivitas Terakhir">Last Activity</th>
                            <th data-lang-en="Product Interest" data-lang-id="Minat Produk">Product Interest</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($behaviors as $behavior)
                            <tr>
                                <td>{{ $behavior->customer?->name ?: '-' }}</td>
                                <td><span class="status-badge status-new">{{ ucfirst($behavior->lifecycle_stage) }}</span></td>
                                <td>
                                    <div><strong>{{ $behavior->engagement_score }}</strong>/100</div>
                                </td>
                                <td>{{ $behavior->last_activity_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $behavior->product_interest ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.customers.behavior.edit', $behavior) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.behavior.destroy', $behavior) }}" data-confirm-en="Delete this behavior?" data-confirm-id="Hapus perilaku ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus perilaku ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="customer-empty" data-lang-en="No behavior data yet." data-lang-id="Belum ada data perilaku.">Belum ada data perilaku.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($behaviors->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $behaviors->firstItem() }}-{{ $behaviors->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $behaviors->total() }} <span data-lang-en="behavior records" data-lang-id="data perilaku">behavior records</span>
                    </div>
                    <div class="pagination-links">
                        @if ($behaviors->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $behaviors->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($behaviors->getUrlRange(max(1, $behaviors->currentPage() - 2), min($behaviors->lastPage(), $behaviors->currentPage() + 2)) as $page => $url)
                            @if ($page === $behaviors->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($behaviors->hasMorePages())
                            <a href="{{ $behaviors->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
