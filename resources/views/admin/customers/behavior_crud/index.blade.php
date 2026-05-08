@extends('admin.layouts.app')

@section('title', 'Behavior - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1>Behavior</h1>
                <p>Data perilaku customer seperti lifecycle stage, engagement, dan aktivitas terakhir.</p>
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
                    >
                    <select name="lifecycle_stage" aria-label="Filter lifecycle stage">
                        <option value="">Semua lifecycle stage</option>
                        @foreach ($lifecycleStageOptions as $stage)
                            <option value="{{ $stage }}" @selected($selectedLifecycleStage === $stage)>{{ ucfirst($stage) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedLifecycleStage)
                        <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                @if ($firstCustomerId)
                    <a href="{{ route('admin.customers.behavior.create', ['customer' => $firstCustomerId]) }}" class="btn btn-primary">Add Behavior</a>
                @else
                    <span class="btn btn-disabled">Add Behavior</span>
                @endif
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
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
                                <td>{{ $behavior->customer?->name ?: '-' }}</td>
                                <td><span class="status-badge status-new">{{ ucfirst($behavior->lifecycle_stage) }}</span></td>
                                <td>
                                    <div><strong>{{ $behavior->engagement_score }}</strong>/100</div>
                                </td>
                                <td>{{ $behavior->last_activity_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $behavior->product_interest ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.customers.behavior.edit', $behavior) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.behavior.destroy', $behavior) }}" onsubmit="return confirm('Delete behavior ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="customer-empty">Belum ada behavior data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($behaviors->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $behaviors->firstItem() }}-{{ $behaviors->lastItem() }} dari {{ $behaviors->total() }} behavior
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
    </section>
@endsection
