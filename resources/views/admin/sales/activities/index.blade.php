@extends('admin.layouts.app')

@section('title', 'Sales Activity Tracking - Krakatau CRM')

@section('content')
    @php
        $typeChips = [
            '' => 'All',
            'call' => 'Call',
            'meeting' => 'Meeting',
            'email' => 'Email',
            'note' => 'Note',
            'follow_up' => 'Follow Up',
        ];
        $chipQuery = fn (array $changes) => array_filter(array_merge([
            'q' => $search,
            'type' => $selectedType,
            'related_type' => $selectedRelatedType,
        ], $changes), fn ($value) => $value !== '' && $value !== null);
    @endphp

    <section class="lead-list-page activity-list-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">SALES WORKSPACE</span>
                <h1>Sales Activity Tracking</h1>
                <p>Tracking aktivitas sales: call, meeting, email, note, dan follow-up.</p>
            </div>
            <a href="{{ route('admin.sales.activities.create') }}" class="btn lead-banner-cta">Add Activity</a>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip activity-kpi-strip" aria-label="Activity summary">
            <div><span>Total Activities</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>Calls</span><strong>{{ number_format($summary['calls']) }}</strong></div>
            <div><span>Meetings</span><strong>{{ number_format($summary['meetings']) }}</strong></div>
            <div><span>Follow Ups</span><strong>{{ number_format($summary['followUps']) }}</strong></div>
        </div>

        <section class="lead-list-workspace activity-list-workspace">
            <div class="lead-smart-filters activity-smart-filters">
                <nav class="lead-filter-chips activity-filter-chips" aria-label="Activity type filters">
                    @foreach ($typeChips as $chipType => $chipLabel)
                        <a href="{{ route('admin.sales.activities.index', $chipQuery(['type' => $chipType])) }}" @class(['active' => $selectedType === $chipType, 'type-'.$chipType => $chipType !== ''])>{{ $chipLabel }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('admin.sales.activities.index') }}" class="lead-list-toolbar activity-list-toolbar">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search activities" aria-label="Search activities">
                    <select name="type" aria-label="Filter activity type">
                        <option value="">Semua type</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                    <select name="related_type" aria-label="Filter related type">
                        <option value="">Semua related</option>
                        @foreach ($relatedTypeOptions as $relatedType)
                            <option value="{{ $relatedType }}" @selected($selectedRelatedType === $relatedType)>{{ ucfirst($relatedType) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    @if ($search || $selectedType || $selectedRelatedType)
                        <a href="{{ route('admin.sales.activities.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
            </div>

            <div class="customer-table-wrap opportunity-table-wrap activity-table-wrap">
                <table class="customer-table opportunity-modern-table activity-modern-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Related</th>
                            <th>Activity Date</th>
                            <th>Assigned To</th>
                            <th>Outcome</th>
                            <th aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td><span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></td>
                                <td>
                                    <div class="activity-subject-cell">
                                        <a href="{{ route('admin.sales.activities.show', $activity) }}" class="opportunity-title-link">{{ $activity->subject }}</a>
                                        <small>{{ \Illuminate\Support\Str::limit($activity->description ?: 'Tidak ada deskripsi', 70) }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="activity-related-cell">
                                        <span>{{ ucfirst($activity->related_type) }}</span>
                                        <small>{{ $activity->related_label }}</small>
                                    </div>
                                </td>
                                <td><span class="activity-date">{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</span></td>
                                <td><span class="lead-owner">{{ $activity->assigned_to ?: 'Unassigned' }}</span></td>
                                <td><span class="activity-outcome">{{ $activity->outcome ?: '-' }}</span></td>
                                <td>
                                    <details class="lead-row-menu">
                                        <summary aria-label="Actions for {{ $activity->subject }}">•••</summary>
                                        <div>
                                            <a href="{{ route('admin.sales.activities.show', $activity) }}">View</a>
                                            <a href="{{ route('admin.sales.activities.edit', $activity) }}">Edit</a>
                                            <button
                                                type="button"
                                                class="js-open-activity-delete-modal"
                                                data-delete-action="{{ route('admin.sales.activities.destroy', $activity) }}"
                                                data-activity-subject="{{ $activity->subject }}"
                                            >Delete</button>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="lead-empty-state activity-empty-state">
                                        <span aria-hidden="true">@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span>
                                        <strong>Belum ada aktivitas sales</strong>
                                        <p>Tambahkan call, meeting, email, note, atau follow-up pertama untuk mulai memantau aktivitas tim sales.</p>
                                        <a href="{{ route('admin.sales.activities.create') }}" class="btn btn-primary">Add Activity</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($activities->hasPages())
                <div class="customer-pagination lead-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $activities->firstItem() }}-{{ $activities->lastItem() }} dari {{ $activities->total() }} activity
                    </div>
                    <div class="pagination-links">
                        @if ($activities->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $activities->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($activities->getUrlRange(max(1, $activities->currentPage() - 2), min($activities->lastPage(), $activities->currentPage() + 2)) as $page => $url)
                            @if ($page === $activities->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($activities->hasMorePages())
                            <a href="{{ $activities->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        <div class="crm-modal-backdrop" data-activity-delete-modal hidden>
            <div class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="activity-delete-modal-title" aria-describedby="activity-delete-modal-description">
                <div class="crm-confirm-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 2.5 20h19z"/><path d="M12 9v5"/><path d="M12 17h.01"/></svg></div>
                <div class="crm-confirm-content">
                    <h2 id="activity-delete-modal-title">Hapus Sales Activity?</h2>
                    <p id="activity-delete-modal-description">Activity ini akan dihapus dari sistem. Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="crm-confirm-target"><span>Activity</span><strong data-activity-delete-subject>-</strong></div>
                </div>
                <form method="POST" action="#" data-activity-delete-form class="crm-confirm-actions">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-muted" data-activity-delete-cancel>Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus Activity</button>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-activity-delete-modal]');
            const form = document.querySelector('[data-activity-delete-form]');
            const subjectTarget = document.querySelector('[data-activity-delete-subject]');
            const cancelButton = document.querySelector('[data-activity-delete-cancel]');
            const openButtons = document.querySelectorAll('.js-open-activity-delete-modal');
            let activeTrigger = null;
            if (!modal || !form || !subjectTarget || !cancelButton) return;

            const closeModal = () => {
                modal.hidden = true;
                form.action = '#';
                subjectTarget.textContent = '-';
                activeTrigger?.focus();
                activeTrigger = null;
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    activeTrigger = button;
                    form.action = button.dataset.deleteAction;
                    subjectTarget.textContent = button.dataset.activitySubject || '-';
                    button.closest('details')?.removeAttribute('open');
                    modal.hidden = false;
                    cancelButton.focus();
                });
            });

            cancelButton.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });
        });
    </script>
@endsection
