@extends('admin.layouts.app')

@section('title', 'Edit Lead - Krakatau CRM')

@section('content')
    <section class="lead-form-page">
        <header class="lead-list-header lead-form-banner">
            <div>
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Edit Lead</h1>
                <p>Perbarui data lead agar proses sales tetap akurat.</p>
            </div>
            <a href="{{ route('admin.sales.leads.show', $lead) }}" class="btn btn-sm lead-banner-secondary">Back</a>
        </header>

        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.sales.leads.update', $lead) }}" class="lead-workspace-form" data-lead-update-form>
            @csrf
            @method('PUT')

            @include('admin.sales.leads._form', [
                'lead' => $lead,
                'customers' => $customers,
                'statusOptions' => $statusOptions,
                'priorityOptions' => $priorityOptions,
            ])

            <div class="lead-form-actions">
                <a href="{{ route('admin.sales.leads.show', $lead) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Lead</button>
            </div>
        </form>

        <div class="crm-modal-backdrop" data-lead-update-modal hidden>
            <div class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="lead-update-modal-title" aria-describedby="lead-update-modal-description">
                <div class="crm-confirm-icon lead-update-confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                </div>
                <div class="crm-confirm-content">
                    <h2 id="lead-update-modal-title">Simpan Perubahan Lead?</h2>
                    <p id="lead-update-modal-description">Pastikan data lead sudah benar sebelum disimpan.</p>
                    <div class="crm-confirm-target">
                        <span>Lead</span>
                        <strong>{{ $lead->name }}</strong>
                    </div>
                </div>
                <div class="crm-confirm-actions">
                    <button type="button" class="btn btn-muted" data-lead-update-cancel>Batal</button>
                    <button type="button" class="btn btn-primary" data-lead-update-confirm>Ya, Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-lead-update-form]');
            const modal = document.querySelector('[data-lead-update-modal]');
            const cancelButton = document.querySelector('[data-lead-update-cancel]');
            const confirmButton = document.querySelector('[data-lead-update-confirm]');
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
