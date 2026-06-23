@extends('admin.layouts.app')

@section('title', 'Opportunity Management - Krakatau CRM')

@section('content')
    @php
        $statusChips = [
            '' => 'All',
            'open' => 'Prospecting',
            'qualified' => 'Qualified',
            'proposal' => 'Proposal',
            'negotiation' => 'Negotiation',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
        $chipQuery = fn (array $changes) => array_filter(array_merge([
            'q' => $search,
            'status' => $selectedStatus,
            'per_page' => $selectedPerPage,
        ], $changes), fn ($value) => $value !== '' && $value !== null);
    @endphp

    <section class="opportunity-list-page">
        <header class="lead-list-header opportunity-list-header">
            <div>
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Opportunity Management</h1>
                <p>Kelola peluang bisnis berdasarkan stage, value, owner, dan estimasi closing.</p>
            </div>
            <a href="{{ route('admin.sales.opportunities.create') }}" class="btn lead-banner-cta">Add Opportunity</a>
        </header>

        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip opportunity-kpi-strip" aria-label="Opportunity summary">
            <div><span>Total Opportunities</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>Open Opportunities</span><strong>{{ number_format($summary['open']) }}</strong></div>
            <div><span>Won Opportunities</span><strong>{{ number_format($summary['won']) }}</strong></div>
            <div><span>Lost Opportunities</span><strong>{{ number_format($summary['lost']) }}</strong></div>
        </div>

        <section class="lead-list-workspace opportunity-list-workspace">
            <div class="lead-smart-filters">
                <nav class="lead-filter-chips opportunity-filter-chips" aria-label="Opportunity stage filters">
                    @foreach ($statusChips as $chipStatus => $chipLabel)
                        <a href="{{ route('admin.sales.opportunities', $chipQuery(['status' => $chipStatus])) }}" @class(['active' => $selectedStatus === $chipStatus, 'stage-'.$chipStatus => $chipStatus !== ''])>{{ $chipLabel }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('admin.sales.opportunities') }}" class="lead-list-toolbar opportunity-list-toolbar">
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search title, company, contact, owner" aria-label="Search opportunities">
                    <select name="per_page" aria-label="Rows per page" onchange="this.form.submit()">
                        @foreach ([10, 20, 50, 100] as $perPageOption)
                            <option value="{{ $perPageOption }}" @selected($selectedPerPage === $perPageOption)>Show {{ $perPageOption }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    @if ($search || $selectedStatus || $selectedPerPage !== 10)
                        <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
            </div>

            <div class="customer-table-wrap opportunity-table-wrap">
                <table class="customer-table opportunity-modern-table">
                    <thead>
                        <tr>
                            <th>Opportunity</th>
                            <th>Company & Contact</th>
                            <th>Estimated Value</th>
                            <th>Probability</th>
                            <th>Stage</th>
                            <th>Expected Close</th>
                            <th>Assigned To</th>
                            <th aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($opportunities as $opportunity)
                            @php
                                $probability = max(0, min(100, (int) $opportunity->probability));
                                $relatedName = $opportunity->lead?->name ?: $opportunity->customer?->name;
                                $relatedType = $opportunity->lead?->name ? 'Lead' : ($opportunity->customer?->name ? 'Customer' : null);
                                $isOverdue = $opportunity->expected_close_date?->isPast() && ! in_array($opportunity->status, ['won', 'lost'], true);
                            @endphp
                            <tr>
                                <td>
                                    <div class="opportunity-primary-cell">
                                        <span class="opportunity-stage-marker stage-{{ $opportunity->status }}"></span>
                                        <div>
                                            <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="opportunity-title-link">{{ $opportunity->title }}</a>
                                            <small>{{ $relatedType ? $relatedType.': '.$relatedName : 'No linked lead or customer' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="opportunity-contact-cell">
                                        <span>{{ $opportunity->company_name ?: '-' }}</span>
                                        <small>{{ $opportunity->contact_name ?: 'No contact' }}</small>
                                    </div>
                                </td>
                                <td><strong class="opportunity-value">Rp {{ number_format((float) $opportunity->estimated_value, 0, ',', '.') }}</strong></td>
                                <td>
                                    <div class="opportunity-probability">
                                        <strong>{{ $probability }}%</strong>
                                        <span><i style="width: {{ $probability }}%"></i></span>
                                    </div>
                                </td>
                                <td><span class="status-badge status-{{ $opportunity->status }}">{{ $statusLabels[$opportunity->status] ?? ucfirst($opportunity->status) }}</span></td>
                                <td><span @class(['opportunity-close-date', 'overdue' => $isOverdue])>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</span></td>
                                <td><span class="lead-owner">{{ $opportunity->assigned_to ?: 'Unassigned' }}</span></td>
                                <td>
                                    <details class="lead-row-menu">
                                        <summary aria-label="Actions for {{ $opportunity->title }}">•••</summary>
                                        <div>
                                            <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}">View</a>
                                            <a href="{{ route('admin.sales.opportunities.edit', $opportunity) }}">Edit</a>
                                            <button
                                                type="button"
                                                class="js-open-opportunity-delete-modal"
                                                data-delete-action="{{ route('admin.sales.opportunities.destroy', $opportunity) }}"
                                                data-opportunity-name="{{ $opportunity->title }}"
                                            >Delete</button>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">
                                    <div class="lead-empty-state opportunity-empty-state">
                                        <span>+</span>
                                        <strong>No opportunities found</strong>
                                        <p>Belum ada opportunity atau tidak ada data yang cocok dengan filter saat ini.</p>
                                        <a href="{{ route('admin.sales.opportunities.create') }}" class="btn btn-sm btn-primary">Add Opportunity</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($opportunities->hasPages())
                <div class="customer-pagination lead-pagination">
                    <div class="pagination-info">Menampilkan {{ $opportunities->firstItem() }}-{{ $opportunities->lastItem() }} dari {{ $opportunities->total() }} opportunity</div>
                    <div class="pagination-links">
                        @if ($opportunities->onFirstPage())<span class="btn btn-sm btn-disabled">Prev</span>@else<a href="{{ $opportunities->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>@endif
                        @foreach ($opportunities->getUrlRange(max(1, $opportunities->currentPage() - 2), min($opportunities->lastPage(), $opportunities->currentPage() + 2)) as $page => $url)
                            @if ($page === $opportunities->currentPage())<span class="btn btn-sm btn-primary">{{ $page }}</span>@else<a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>@endif
                        @endforeach
                        @if ($opportunities->hasMorePages())<a href="{{ $opportunities->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>@else<span class="btn btn-sm btn-disabled">Next</span>@endif
                    </div>
                </div>
            @endif
        </section>

        <div class="crm-modal-backdrop" data-opportunity-delete-modal hidden>
            <div class="crm-confirm-modal opportunity-delete-modal" role="dialog" aria-modal="true" aria-labelledby="opportunity-delete-modal-title" aria-describedby="opportunity-delete-modal-description">
                <div class="crm-confirm-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 2.5 20h19z"/><path d="M12 9v5"/><path d="M12 17h.01"/></svg></div>
                <div class="crm-confirm-content">
                    <h2 id="opportunity-delete-modal-title">Hapus Opportunity?</h2>
                    <p id="opportunity-delete-modal-description">Opportunity ini akan dihapus dari sistem. Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="crm-confirm-target"><span>Opportunity</span><strong data-opportunity-delete-name>-</strong></div>
                </div>
                <form method="POST" action="#" data-opportunity-delete-form class="crm-confirm-actions">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-muted" data-opportunity-delete-cancel>Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus Opportunity</button>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-opportunity-delete-modal]');
            const form = document.querySelector('[data-opportunity-delete-form]');
            const nameTarget = document.querySelector('[data-opportunity-delete-name]');
            const cancelButton = document.querySelector('[data-opportunity-delete-cancel]');
            const openButtons = document.querySelectorAll('.js-open-opportunity-delete-modal');
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
                    nameTarget.textContent = button.dataset.opportunityName || '-';
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
