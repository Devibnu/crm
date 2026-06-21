@extends('admin.layouts.app')

@section('title', 'Edit Quotation - Krakatau CRM')

@section('content')
    <section class="lead-form-page quotation-form-page">
        <header class="lead-list-header lead-form-banner">
            <div>
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Edit Quotation</h1>
                <p>{{ $quotation->quote_number }} · {{ $quotation->title }}</p>
            </div>
            <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-sm lead-banner-secondary">Back</a>
        </header>

        <form method="POST" action="{{ route('admin.sales.deals.update', $quotation) }}" class="lead-workspace-form quotation-workspace-form" data-quotation-update-form>
            @csrf
            @method('PUT')

            @include('admin.sales.deals._form', [
                'quotation' => $quotation,
                'opportunities' => $opportunities,
                'customers' => $customers,
                'statusOptions' => $statusOptions,
            ])

            <div class="lead-form-actions">
                <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Quotation</button>
            </div>
        </form>

        <div class="crm-modal-backdrop" data-quotation-update-modal hidden>
            <div class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="quotation-update-modal-title" aria-describedby="quotation-update-modal-description">
                <div class="crm-confirm-icon quotation-update-confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12.5 9.5 17 19 7.5"/></svg>
                </div>
                <div class="crm-confirm-content">
                    <h2 id="quotation-update-modal-title">Simpan Perubahan Quotation?</h2>
                    <p id="quotation-update-modal-description">Pastikan data quotation, amount, status, issued date, dan valid until sudah benar sebelum disimpan.</p>
                    <div class="crm-confirm-target activity-confirm-summary">
                        <div><span>Quote Number</span><strong data-confirm-quote-number>-</strong></div>
                        <div><span>Title</span><strong data-confirm-quote-title>-</strong></div>
                        <div><span>Amount</span><strong data-confirm-quote-amount>-</strong></div>
                        <div><span>Status</span><strong data-confirm-quote-status>-</strong></div>
                        <div><span>Opportunity</span><strong data-confirm-quote-opportunity>-</strong></div>
                    </div>
                </div>
                <div class="crm-confirm-actions">
                    <button type="button" class="btn btn-muted" data-quotation-update-cancel>Batal</button>
                    <button type="button" class="btn btn-primary" data-quotation-update-confirm>Ya, Simpan Quotation</button>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-quotation-update-form]');
            const modal = document.querySelector('[data-quotation-update-modal]');
            const cancelButton = document.querySelector('[data-quotation-update-cancel]');
            const confirmButton = document.querySelector('[data-quotation-update-confirm]');
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
                const opportunity = form.elements.opportunity_id;
                const status = form.elements.status;
                const amount = Number(form.elements.amount.value);
                document.querySelector('[data-confirm-quote-number]').textContent = form.elements.quote_number.value.trim() || '-';
                document.querySelector('[data-confirm-quote-title]').textContent = form.elements.title.value.trim() || '-';
                document.querySelector('[data-confirm-quote-amount]').textContent = Number.isFinite(amount)
                    ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount)
                    : '-';
                document.querySelector('[data-confirm-quote-status]').textContent = status.options[status.selectedIndex]?.text || '-';
                document.querySelector('[data-confirm-quote-opportunity]').textContent = opportunity.value
                    ? opportunity.options[opportunity.selectedIndex]?.text || '-'
                    : '-';
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
