@extends('admin.layouts.app')

@section('title', $activity->subject.' - Sales Activity - Krakatau CRM')

@section('content')
    @php
        $activityTypeLabel = ucwords(str_replace('_', ' ', $activity->type));
        $relatedRecord = match ($activity->related_type) {
            'lead' => $activity->relatedLead,
            'opportunity' => $activity->relatedOpportunity,
            'customer' => $activity->relatedCustomer,
            default => null,
        };
        $backUrl = match ($activity->related_type) {
            'lead' => $relatedRecord ? route('admin.sales.leads.show', $relatedRecord) : route('admin.sales.leads'),
            'opportunity' => $relatedRecord ? route('admin.sales.opportunities.show', $relatedRecord) : route('admin.sales.opportunities'),
            default => route('admin.sales.activities.index'),
        };
        $backLabel = match ($activity->related_type) {
            'lead' => 'Back to Lead',
            'opportunity' => 'Back to Opportunity',
            default => 'Back to Sales Activity',
        };
    @endphp

    <section class="crm-record-page activity-record-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-detail-banner activity-detail-banner">
            <div class="crm-record-heading">
                <span class="crm-record-kicker">Sales Workspace</span>
                <div class="crm-record-title-row">
                    <h1>{{ $activity->subject }}</h1>
                    <span class="status-badge activity-{{ $activity->type }} activity-banner-type">{{ $activityTypeLabel }}</span>
                </div>
                <p>{{ ucfirst($activity->related_type) }}: {{ $activity->related_label }} · {{ $activity->activity_at?->format('d M Y H:i') ?: 'No activity date' }}</p>
            </div>
            <div class="lead-detail-action-stack">
                <div class="crm-record-actions">
                    <a href="{{ route('admin.sales.activities.edit', $activity) }}" class="btn btn-sm lead-banner-cta">Edit</a>
                    <button type="button" class="btn btn-sm activity-banner-delete" data-open-activity-delete>Delete</button>
                </div>
                <a href="{{ $backUrl }}" class="lead-detail-back lead-detail-back-secondary">{{ $backLabel }}</a>
            </div>
        </header>

        <div class="crm-metadata-row activity-metadata-row">
            <div><span>Type</span><strong>{{ $activityTypeLabel }}</strong></div>
            <div><span>Related Data</span><strong>{{ $activity->related_label }}</strong></div>
            <div><span>Activity Date</span><strong>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</strong></div>
            <div><span>Assigned To</span><strong>{{ $activity->assigned_to ?: '-' }}</strong></div>
            <div><span>Outcome</span><strong>{{ $activity->outcome ?: '-' }}</strong></div>
        </div>

        <div class="crm-record-workspace activity-detail-workspace">
            <aside class="crm-workspace-sidebar crm-details-sidebar">
                <section class="crm-workspace-section">
                    <h2>Activity Details</h2>
                    <dl class="crm-property-list">
                        <div><dt>Type</dt><dd>{{ $activityTypeLabel }}</dd></div>
                        <div><dt>Subject</dt><dd>{{ $activity->subject }}</dd></div>
                        <div><dt>Activity Date</dt><dd>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</dd></div>
                        <div><dt>Assigned To</dt><dd>{{ $activity->assigned_to ?: '-' }}</dd></div>
                        <div><dt>Outcome</dt><dd>{{ $activity->outcome ?: '-' }}</dd></div>
                    </dl>
                </section>
            </aside>

            <main class="crm-workspace-main activity-description-workspace">
                <section class="crm-tab-content">
                    <div class="crm-content-heading"><div><h2>Description / Notes</h2><p>Recorded context for this sales activity.</p></div></div>
                    @if (filled($activity->description))
                        <div class="activity-description-content">{{ $activity->description }}</div>
                    @else
                        <div class="crm-workspace-empty activity-description-empty">No description available.</div>
                    @endif
                </section>
            </main>

            <aside class="crm-workspace-sidebar crm-related-sidebar">
                <section class="crm-workspace-section">
                    <h2>Related Record</h2>
                    <div class="crm-related-list">
                        <div><span>Related Type</span><strong>{{ ucfirst($activity->related_type) }}</strong></div>
                        <div><span>Related Data</span><strong>{{ $activity->related_label }}</strong></div>
                    </div>
                    @if ($activity->related_type === 'lead' && $relatedRecord)
                        <a href="{{ route('admin.sales.leads.show', $relatedRecord) }}" class="crm-related-record-link"><strong>{{ $relatedRecord->name }}</strong><span>Open lead workspace</span></a>
                    @elseif ($activity->related_type === 'opportunity' && $relatedRecord)
                        <a href="{{ route('admin.sales.opportunities.show', $relatedRecord) }}" class="crm-related-record-link"><strong>{{ $relatedRecord->title }}</strong><span>Open opportunity workspace</span></a>
                    @endif
                </section>
            </aside>
        </div>

        <div class="crm-modal-backdrop" data-activity-delete-modal hidden>
            <div class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="activity-delete-modal-title" aria-describedby="activity-delete-modal-description">
                <div class="crm-confirm-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 2.5 20h19z"/><path d="M12 9v5"/><path d="M12 17h.01"/></svg></div>
                <div class="crm-confirm-content">
                    <h2 id="activity-delete-modal-title">Hapus Sales Activity?</h2>
                    <p id="activity-delete-modal-description">Data activity akan dihapus dari sistem. Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="crm-confirm-target"><span>Activity</span><strong>{{ $activity->subject }}</strong></div>
                </div>
                <form method="POST" action="{{ route('admin.sales.activities.destroy', $activity) }}" class="crm-confirm-actions">
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
            const openButton = document.querySelector('[data-open-activity-delete]');
            const cancelButton = document.querySelector('[data-activity-delete-cancel]');
            if (!modal || !openButton || !cancelButton) return;

            const closeModal = () => {
                modal.hidden = true;
                openButton.focus();
            };

            openButton.addEventListener('click', () => {
                modal.hidden = false;
                cancelButton.focus();
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
