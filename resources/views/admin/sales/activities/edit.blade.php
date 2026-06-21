@extends('admin.layouts.app')

@section('title', 'Edit Sales Activity - Krakatau CRM')

@section('content')
    <section class="lead-form-page activity-edit-page">
        <header class="lead-list-header lead-form-banner">
            <div>
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Edit Sales Activity</h1>
                <p>{{ $activity->subject }} · {{ ucfirst($activity->related_type) }}: {{ $activity->related_label }}</p>
            </div>
            <a href="{{ route('admin.sales.activities.show', $activity) }}" class="btn btn-sm lead-banner-secondary">Back</a>
        </header>

        <form method="POST" action="{{ route('admin.sales.activities.update', $activity) }}" class="lead-workspace-form activity-edit-form" data-activity-update-form>
            @csrf
            @method('PUT')

            @include('admin.sales.activities._form')

            <div class="lead-form-actions">
                <a href="{{ route('admin.sales.activities.show', $activity) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Activity</button>
            </div>
        </form>

        <div class="crm-modal-backdrop" data-activity-update-modal hidden>
            <div class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="activity-update-modal-title" aria-describedby="activity-update-modal-description">
                <div class="crm-confirm-icon activity-save-confirm-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5 9.5 17 19 7.5"/></svg></div>
                <div class="crm-confirm-content">
                    <h2 id="activity-update-modal-title">Simpan Perubahan Activity?</h2>
                    <p id="activity-update-modal-description">Pastikan detail activity sudah benar sebelum disimpan.</p>
                    <div class="crm-confirm-target"><span>Activity</span><strong>{{ $activity->subject }}</strong></div>
                </div>
                <div class="crm-confirm-actions">
                    <button type="button" class="btn btn-muted" data-activity-update-cancel>Batal</button>
                    <button type="button" class="btn btn-primary" data-activity-update-confirm>Ya, Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-activity-update-form]');
            const modal = document.querySelector('[data-activity-update-modal]');
            const cancelButton = document.querySelector('[data-activity-update-cancel]');
            const confirmButton = document.querySelector('[data-activity-update-confirm]');
            let confirmed = false;
            let activeTrigger = null;
            if (!form || !modal || !cancelButton || !confirmButton) return;

            const closeModal = () => {
                modal.hidden = true;
                activeTrigger?.focus();
                activeTrigger = null;
            };

            form.addEventListener('submit', event => {
                if (confirmed) return;
                event.preventDefault();
                activeTrigger = event.submitter || document.activeElement;
                modal.hidden = false;
                cancelButton.focus();
            });
            cancelButton.addEventListener('click', closeModal);
            confirmButton.addEventListener('click', () => {
                confirmed = true;
                modal.hidden = true;
                form.requestSubmit();
            });
            modal.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });
        });
    </script>
@endsection
