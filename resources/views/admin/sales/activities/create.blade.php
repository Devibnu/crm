@extends('admin.layouts.app')

@section('title', 'Add Sales Activity - Krakatau CRM')

@section('content')
    @php
        $composerRelatedType = old('related_type', $prefillRelatedType ?: 'lead');
        $composerRelatedId = old('related_id', $prefillRelatedId);
        $backUrl = match ($composerRelatedType) {
            'lead' => $composerRelatedId ? route('admin.sales.leads.show', $composerRelatedId) : route('admin.sales.leads'),
            'opportunity' => $composerRelatedId ? route('admin.sales.opportunities.show', $composerRelatedId) : route('admin.sales.opportunities'),
            default => route('admin.sales.activities.index'),
        };
    @endphp

    <section class="activity-composer-page">
        <form method="POST" action="{{ route('admin.sales.activities.store') }}" class="activity-composer-form" data-activity-composer>
            @csrf

            <header class="lead-list-header activity-composer-header">
                <div>
                    <span class="crm-record-kicker">Sales Workspace</span>
                    <h1>Add Sales Activity</h1>
                    <p>Catat call, meeting, follow-up, atau reminder untuk lead/opportunity.</p>
                </div>
                <div class="activity-composer-actions">
                    <a href="{{ $backUrl }}" class="btn btn-sm lead-banner-secondary">Back</a>
                    <button type="submit" class="btn btn-sm lead-banner-cta">Save Activity</button>
                </div>
            </header>

            <div class="activity-composer-layout">
                <main class="activity-composer-main">
                    @include('admin.sales.activities._form')
                </main>

                <aside class="activity-composer-sidebar">
                    <section class="activity-composer-side-card">
                        <h2>Related Record</h2>
                        <div class="activity-related-empty" data-related-empty>Pilih lead atau opportunity untuk melihat konteks aktivitas.</div>
                        <dl class="activity-related-summary" data-related-summary hidden>
                            <div><dt>Record Type</dt><dd data-context-type>-</dd></div>
                            <div><dt>Related Data</dt><dd data-context-record>-</dd></div>
                            <div><dt>Activity Type</dt><dd data-context-activity>Call</dd></div>
                        </dl>
                    </section>

                    <section class="activity-composer-side-card">
                        <h2>Activity Workflow</h2>
                        <ol class="activity-type-guide">
                            <li data-activity-step="call">Call</li>
                            <li data-activity-step="meeting">Meeting</li>
                            <li data-activity-step="follow_up">Follow Up</li>
                            <li data-activity-step="task">Task</li>
                        </ol>
                    </section>

                    <section class="activity-composer-side-card">
                        <h2>Best Practices</h2>
                        <ul class="activity-best-practices">
                            <li>Gunakan subject yang jelas.</li>
                            <li>Isi outcome setelah aktivitas selesai.</li>
                            <li>Tambahkan follow-up jika perlu.</li>
                            <li>Pastikan activity terkait lead/opportunity yang benar.</li>
                        </ul>
                    </section>
                </aside>
            </div>
        </form>

        <div class="crm-modal-backdrop" data-activity-save-modal hidden>
            <div class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="activity-save-modal-title" aria-describedby="activity-save-modal-description">
                <div class="crm-confirm-icon activity-save-confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                </div>
                <div class="crm-confirm-content">
                    <h2 id="activity-save-modal-title">Simpan Sales Activity?</h2>
                    <p id="activity-save-modal-description">Pastikan detail activity sudah benar sebelum disimpan.</p>
                    <div class="crm-confirm-target activity-confirm-summary">
                        <div><span>Related Type</span><strong data-confirm-related-type>-</strong></div>
                        <div><span>Related Data</span><strong data-confirm-related-data>-</strong></div>
                        <div><span>Activity Type</span><strong data-confirm-activity-type>-</strong></div>
                        <div><span>Subject</span><strong data-confirm-subject>-</strong></div>
                    </div>
                </div>
                <div class="crm-confirm-actions">
                    <button type="button" class="btn btn-muted" data-activity-save-cancel>Batal</button>
                    <button type="button" class="btn btn-primary" data-activity-save-confirm>Ya, Simpan Activity</button>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const composer = document.querySelector('[data-activity-composer]');
            if (!composer) return;

            const relatedType = composer.elements.related_type;
            const relatedId = composer.elements.related_id;
            const activityType = composer.elements.type;
            const summary = composer.querySelector('[data-related-summary]');
            const emptyState = composer.querySelector('[data-related-empty]');
            const modal = document.querySelector('[data-activity-save-modal]');
            const cancelButton = document.querySelector('[data-activity-save-cancel]');
            const confirmButton = document.querySelector('[data-activity-save-confirm]');
            let confirmed = false;
            let activeTrigger = null;

            const updateContext = () => {
                const selectedRecord = relatedId.options[relatedId.selectedIndex];
                const hasRecord = Boolean(relatedId.value && selectedRecord && !selectedRecord.hidden);
                const activityLabel = activityType.options[activityType.selectedIndex]?.text || '-';

                composer.querySelector('[data-context-type]').textContent = relatedType.options[relatedType.selectedIndex]?.text || '-';
                composer.querySelector('[data-context-record]').textContent = hasRecord ? selectedRecord.text.replace(/^[^:]+:\s*/, '') : '-';
                composer.querySelector('[data-context-activity]').textContent = activityLabel;
                summary.hidden = !hasRecord;
                emptyState.hidden = hasRecord;

                const activeStep = ['call', 'meeting', 'follow_up'].includes(activityType.value) ? activityType.value : 'task';
                composer.querySelectorAll('[data-activity-step]').forEach(step => {
                    step.classList.toggle('current', step.dataset.activityStep === activeStep);
                });
            };

            [relatedType, relatedId, activityType].forEach(input => input.addEventListener('change', updateContext));

            const closeModal = () => {
                modal.hidden = true;
                activeTrigger?.focus();
                activeTrigger = null;
            };

            composer.addEventListener('submit', event => {
                if (confirmed) return;

                event.preventDefault();
                activeTrigger = event.submitter || document.activeElement;
                const selectedRecord = relatedId.options[relatedId.selectedIndex];
                document.querySelector('[data-confirm-related-type]').textContent = relatedType.options[relatedType.selectedIndex]?.text || '-';
                document.querySelector('[data-confirm-related-data]').textContent = selectedRecord?.text.replace(/^[^:]+:\s*/, '') || '-';
                document.querySelector('[data-confirm-activity-type]').textContent = activityType.options[activityType.selectedIndex]?.text || '-';
                document.querySelector('[data-confirm-subject]').textContent = composer.elements.subject.value.trim() || '-';
                modal.hidden = false;
                cancelButton.focus();
            });

            cancelButton.addEventListener('click', closeModal);
            confirmButton.addEventListener('click', () => {
                confirmed = true;
                modal.hidden = true;
                composer.requestSubmit();
            });
            modal.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });
            updateContext();
        });
    </script>
@endsection
