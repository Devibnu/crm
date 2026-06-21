@extends('admin.layouts.app')

@section('title', 'Add Lead - Krakatau CRM')

@section('content')
    <section class="lead-create-page">
        <form method="POST" action="{{ route('admin.sales.leads.store') }}" class="lead-create-form" data-lead-create-form>
            @csrf

            <header class="lead-list-header lead-create-header">
                <div>
                    <span class="crm-record-kicker">Sales Workspace</span>
                    <h1>Add Lead</h1>
                    <p>Tambahkan prospek baru untuk proses sales, follow-up, dan konversi menjadi opportunity.</p>
                </div>
                <div class="lead-create-header-actions">
                    <a href="{{ route('admin.sales.leads') }}" class="btn btn-sm lead-banner-secondary">Back</a>
                    <button type="submit" class="btn btn-sm lead-banner-cta">Save Lead</button>
                </div>
            </header>

            <div class="lead-create-layout">
                <main class="lead-create-main">
                    @include('admin.sales.leads._form', [
                        'customers' => $customers,
                        'statusOptions' => $statusOptions,
                        'priorityOptions' => $priorityOptions,
                    ])
                </main>

                <aside class="lead-create-sidebar">
                    <section class="lead-create-side-card lead-summary-card">
                        <h2>Lead Summary</h2>
                        <p class="lead-summary-empty" data-summary-empty>Lengkapi data lead untuk melihat ringkasan.</p>
                        <dl class="lead-summary-preview" data-summary-preview hidden>
                            <div><dt>Lead Name</dt><dd data-preview="name">-</dd></div>
                            <div><dt>Company</dt><dd data-preview="company_name">-</dd></div>
                            <div><dt>Source</dt><dd data-preview="source">-</dd></div>
                            <div><dt>Status</dt><dd data-preview="status">New</dd></div>
                            <div><dt>Priority</dt><dd data-preview="priority">Medium</dd></div>
                            <div><dt>Owner</dt><dd data-preview="assigned_to">-</dd></div>
                        </dl>
                    </section>

                    <section class="lead-create-side-card">
                        <h2>Lead Workflow</h2>
                        <ol class="lead-workflow-guide">
                            <li class="current">Lead</li>
                            <li>Qualified</li>
                            <li>Opportunity</li>
                            <li>Quotation</li>
                            <li>Won</li>
                        </ol>
                    </section>

                    <section class="lead-create-side-card">
                        <h2>Best Practices</h2>
                        <ul class="lead-best-practices">
                            <li>Isi nomor telepon yang valid.</li>
                            <li>Tentukan owner lead.</li>
                            <li>Gunakan source untuk tracking marketing.</li>
                            <li>Tambahkan notes jika diperlukan.</li>
                        </ul>
                    </section>
                </aside>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-lead-create-form]');
            if (!form) return;

            const preview = form.querySelector('[data-summary-preview]');
            const emptyState = form.querySelector('[data-summary-empty]');
            const previewFields = ['name', 'company_name', 'source', 'status', 'priority', 'assigned_to'];
            const meaningfulFields = ['name', 'company_name', 'source', 'assigned_to'];
            const displayValue = input => input?.tagName === 'SELECT'
                ? input.options[input.selectedIndex]?.text || '-'
                : input?.value.trim() || '-';

            const updateSummary = () => {
                previewFields.forEach(name => {
                    const output = preview.querySelector(`[data-preview="${name}"]`);
                    if (output) output.textContent = displayValue(form.elements[name]);
                });

                const hasLeadContext = meaningfulFields.some(name => form.elements[name]?.value.trim());
                preview.hidden = !hasLeadContext;
                emptyState.hidden = hasLeadContext;
            };

            previewFields.forEach(name => {
                form.elements[name]?.addEventListener('input', updateSummary);
                form.elements[name]?.addEventListener('change', updateSummary);
            });

            updateSummary();
        })();
    </script>
@endsection
