@extends('admin.layouts.app')

@section('title', 'Lead Management - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <h1>Lead Management</h1>
                <p>Kelola data lead: capture, assign, dan kualifikasi.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <form method="GET" action="{{ route('admin.sales.leads') }}" class="customer-search-form">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari name, company, email, phone, assigned" aria-label="Search leads">
                    <select name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <select name="priority" aria-label="Filter priority">
                        <option value="">Semua priority</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedStatus || $selectedPriority)
                        <a href="{{ route('admin.sales.leads') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                <a href="{{ route('admin.sales.leads.create') }}" class="btn btn-primary">Add Lead</a>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Lead Name</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Source</th>
                            <th>Score</th>
                            <th>Temperature</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <td>
                                    <div>{{ $lead->name }}</div>
                                    <small>{{ $lead->customer?->name ? 'Customer: '.$lead->customer->name : '-' }}</small>
                                </td>
                                <td>{{ $lead->company_name ?: '-' }}</td>
                                <td>
                                    <div>{{ $lead->email ?: '-' }}</div>
                                    <small>{{ $lead->phone ?: '-' }}</small>
                                </td>
                                <td>
                                    @if (($lead->lead_source ?: $lead->source) === 'whatsapp')
                                        <span class="status-badge source-whatsapp">WhatsApp</span>
                                    @else
                                        {{ $lead->source ?: '-' }}
                                    @endif
                                </td>
                                <td><span class="status-badge lead-score-badge">{{ (int) $lead->lead_score }}</span></td>
                                <td><span class="status-badge lead-temperature-{{ $lead->lead_temperature ?: 'cold' }}">{{ ucfirst($lead->lead_temperature ?: 'cold') }}</span></td>
                                <td><span class="status-badge status-{{ $lead->status }}">{{ ucfirst($lead->status) }}</span></td>
                                <td><span class="status-badge priority-{{ $lead->priority }}">{{ ucfirst($lead->priority) }}</span></td>
                                <td>{{ $lead->assigned_to ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.sales.leads.show', $lead) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.sales.leads.edit', $lead) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.sales.leads.destroy', $lead) }}" onsubmit="return confirm('Delete lead ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="customer-empty">Belum ada lead.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leads->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $leads->firstItem() }}-{{ $leads->lastItem() }} dari {{ $leads->total() }} lead
                    </div>
                    <div class="pagination-links">
                        @if ($leads->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $leads->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($leads->getUrlRange(max(1, $leads->currentPage() - 2), min($leads->lastPage(), $leads->currentPage() + 2)) as $page => $url)
                            @if ($page === $leads->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($leads->hasMorePages())
                            <a href="{{ $leads->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
