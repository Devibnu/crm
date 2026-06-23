@extends('admin.layouts.app')

@section('title', 'Lead Management - Krakatau CRM')

@section('content')
    @php
        $statusChips = [
            '' => 'All',
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'converted' => 'Converted',
        ];
        $chipQuery = fn (array $changes) => array_filter(array_merge([
            'q' => $search,
            'status' => $selectedStatus,
            'priority' => $selectedPriority,
            'per_page' => $selectedPerPage,
        ], $changes), fn ($value) => $value !== '' && $value !== null);
    @endphp

    <section class="lead-list-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">Sales workspace</span>
                <h1>Lead Management</h1>
                <p>Prioritaskan lead berdasarkan status, priority, source, dan owner.</p>
            </div>
            <a href="{{ route('admin.sales.leads.create') }}" class="btn lead-banner-cta">Add Lead</a>
        </header>

        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip" aria-label="Lead summary">
            <div><span>Total Leads</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>New Leads</span><strong>{{ number_format($summary['new']) }}</strong></div>
            <div><span>Qualified Leads</span><strong>{{ number_format($summary['qualified']) }}</strong></div>
            <div><span>Converted Leads</span><strong>{{ number_format($summary['converted']) }}</strong></div>
        </div>

        <section class="lead-list-workspace">
            <div class="lead-smart-filters">
                <nav class="lead-filter-chips" aria-label="Lead status filters">
                    @foreach ($statusChips as $chipStatus => $chipLabel)
                        <a href="{{ route('admin.sales.leads', $chipQuery(['status' => $chipStatus])) }}" @class(['active' => $selectedStatus === $chipStatus])>{{ $chipLabel }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('admin.sales.leads') }}" class="lead-list-toolbar">
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search lead, company, contact, owner" aria-label="Search leads">
                    <select name="priority" aria-label="Filter priority">
                        <option value="">All priorities</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                    <select name="per_page" aria-label="Rows per page" onchange="this.form.submit()">
                        @foreach ([10, 20, 50, 100] as $perPageOption)
                            <option value="{{ $perPageOption }}" @selected($selectedPerPage === $perPageOption)>Show {{ $perPageOption }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    @if ($search || $selectedStatus || $selectedPriority || $selectedPerPage !== 10)
                        <a href="{{ route('admin.sales.leads') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
            </div>

            <div class="customer-table-wrap lead-table-wrap">
                <table class="customer-table lead-modern-table">
                    <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Contact</th>
                            <th>Source</th>
                            <th>Status & Priority</th>
                            <th>Assigned To</th>
                            <th aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            @php
                                $source = $lead->lead_source ?: $lead->source;
                                $initial = mb_strtoupper(mb_substr($lead->name, 0, 1));
                            @endphp
                            <tr>
                                <td>
                                    <div class="lead-primary-cell">
                                        <span class="lead-avatar lead-avatar-priority-{{ $lead->priority }}">{{ $initial }}</span>
                                        <div>
                                            <a href="{{ route('admin.sales.leads.show', $lead) }}" class="lead-name-link">{{ $lead->name }}</a>
                                            <p>{{ $lead->company_name ?: $lead->customer?->name ?: 'No company' }}</p>
                                            <small>{{ $lead->customer?->name ? 'Customer: '.$lead->customer->name : 'No linked customer' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="lead-contact-cell">
                                        <span>{{ $lead->email ?: '-' }}</span>
                                        <small>{{ $lead->phone ?: $lead->whatsapp ?: '-' }}</small>
                                    </div>
                                </td>
                                <td><span @class(['lead-source-label', 'source-whatsapp' => strtolower((string) $source) === 'whatsapp'])>{{ $source ? ucfirst($source) : 'No source' }}</span></td>
                                <td>
                                    <div class="lead-state-cell">
                                        <span class="status-badge status-{{ $lead->status }}">{{ ucfirst($lead->status) }}</span>
                                        <small class="priority-text priority-{{ $lead->priority }}">{{ ucfirst($lead->priority) }} priority</small>
                                    </div>
                                </td>
                                <td><span class="lead-owner">{{ $lead->assigned_to ?: 'Unassigned' }}</span></td>
                                <td>
                                    <details class="lead-row-menu">
                                        <summary aria-label="Actions for {{ $lead->name }}">•••</summary>
                                        <div>
                                            <a href="{{ route('admin.sales.leads.show', $lead) }}">View</a>
                                            <a href="{{ route('admin.sales.leads.edit', $lead) }}">Edit</a>
                                            <button
                                                type="button"
                                                class="js-open-lead-delete-modal"
                                                data-delete-action="{{ route('admin.sales.leads.destroy', $lead) }}"
                                                data-lead-name="{{ $lead->name }}"
                                            >Delete</button>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="customer-empty">
                                    <div class="lead-empty-state">
                                        <span>+</span>
                                        <strong>No leads found</strong>
                                        <p>Belum ada lead atau tidak ada data yang cocok dengan filter saat ini.</p>
                                        <a href="{{ route('admin.sales.leads.create') }}" class="btn btn-sm btn-primary">Add Lead</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leads->hasPages())
                <div class="customer-pagination lead-pagination">
                    <div class="pagination-info">Menampilkan {{ $leads->firstItem() }}-{{ $leads->lastItem() }} dari {{ $leads->total() }} lead</div>
                    <div class="pagination-links">
                        @if ($leads->onFirstPage())<span class="btn btn-sm btn-disabled">Prev</span>@else<a href="{{ $leads->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>@endif
                        @foreach ($leads->getUrlRange(max(1, $leads->currentPage() - 2), min($leads->lastPage(), $leads->currentPage() + 2)) as $page => $url)
                            @if ($page === $leads->currentPage())<span class="btn btn-sm btn-primary">{{ $page }}</span>@else<a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>@endif
                        @endforeach
                        @if ($leads->hasMorePages())<a href="{{ $leads->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>@else<span class="btn btn-sm btn-disabled">Next</span>@endif
                    </div>
                </div>
            @endif
        </section>

        <div class="crm-modal-backdrop" data-lead-delete-modal hidden>
            <div class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="lead-delete-modal-title" aria-describedby="lead-delete-modal-description">
                <div class="crm-confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 3 2.5 20h19z"/><path d="M12 9v5"/><path d="M12 17h.01"/></svg>
                </div>
                <div class="crm-confirm-content">
                    <h2 id="lead-delete-modal-title">Hapus Lead?</h2>
                    <p id="lead-delete-modal-description">Data lead akan dihapus dari sistem. Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="crm-confirm-target">
                        <span>Lead</span>
                        <strong data-lead-delete-name>-</strong>
                    </div>
                </div>
                <form method="POST" action="#" data-lead-delete-form class="crm-confirm-actions">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-muted" data-lead-delete-cancel>Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus Lead</button>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-lead-delete-modal]');
            const form = document.querySelector('[data-lead-delete-form]');
            const nameTarget = document.querySelector('[data-lead-delete-name]');
            const cancelButton = document.querySelector('[data-lead-delete-cancel]');
            const openButtons = document.querySelectorAll('.js-open-lead-delete-modal');
            let activeTrigger = null;

            if (!modal || !form || !nameTarget || !cancelButton) return;

            const closeModal = () => {
                modal.hidden = true;
                form.action = '#';
                nameTarget.textContent = '-';
                activeTrigger?.focus();
                activeTrigger = null;
            };

            openButtons.forEach(button => {
                button.addEventListener('click', () => {
                    activeTrigger = button;
                    form.action = button.dataset.deleteAction;
                    nameTarget.textContent = button.dataset.leadName || '-';
                    button.closest('details')?.removeAttribute('open');
                    modal.hidden = false;
                    cancelButton.focus();
                });
            });

            cancelButton.addEventListener('click', closeModal);
            modal.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });
        });
    </script>
@endsection
