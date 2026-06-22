@extends('admin.layouts.app')

@section('title', 'Pipeline & Forecasting - Krakatau CRM')

@section('content')
    @php
        $totalOpportunities = collect($stageRows)->sum('count');
        $activeFilterCount = collect($filters)->filter(fn ($value) => $value !== '')->count();
        $stageRowsByKey = collect($stageRows)->keyBy('key');
    @endphp

    <section class="crm-pipeline-page pipeline-workspace-page">
        <header class="lead-list-header pipeline-workspace-banner">
            <div class="pipeline-heading-block">
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Pipeline & Forecasting</h1>
                <p>Pantau peluang bisnis berdasarkan stage, nilai, probabilitas, dan estimasi closing.</p>
            </div>
            <div class="pipeline-banner-meta">
                <span class="pipeline-count-badge">{{ number_format($totalOpportunities) }} opportunities · {{ count($stages) }} stages</span>
            </div>
        </header>

        <form method="GET" action="{{ route('admin.sales.pipeline') }}" class="crm-pipeline-toolbar pipeline-filter-toolbar">
            <label><span>Owner</span><input type="text" name="assigned_to" value="{{ $filters['assigned_to'] }}" placeholder="All owners"></label>
            <label><span>Close Date From</span><input type="date" name="expected_close_date_from" value="{{ $filters['expected_close_date_from'] }}"></label>
            <label><span>Close Date To</span><input type="date" name="expected_close_date_to" value="{{ $filters['expected_close_date_to'] }}"></label>
            <div class="pipeline-filter-actions">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                @if ($activeFilterCount > 0)
                    <a href="{{ route('admin.sales.pipeline') }}" class="btn btn-sm btn-muted">Reset</a>
                @endif
            </div>
        </form>

        <div class="crm-pipeline-summary pipeline-kpi-grid" aria-label="Pipeline summary">
            <div><span>Total Pipeline</span><strong>Rp {{ number_format((float) $summary['total_pipeline_value'], 2, ',', '.') }}</strong></div>
            <div><span>Weighted Forecast</span><strong>Rp {{ number_format((float) $summary['weighted_forecast'], 2, ',', '.') }}</strong></div>
            <div><span>Won Value</span><strong>Rp {{ number_format((float) $summary['won_value'], 2, ',', '.') }}</strong></div>
            <div><span>Open Opportunities</span><strong>{{ number_format($summary['open_opportunities_count']) }}</strong></div>
        </div>

        <div class="pipeline-board-heading">
            <div><h2>Opportunity Stages</h2><p>Prioritas berdasarkan closing date, probability, dan value.</p></div>
            <div class="pipeline-legend" aria-label="Pipeline status legend">
                <span class="overdue">Overdue</span>
                <span class="soon">Closing soon</span>
                <span class="won">Won</span>
            </div>
        </div>

        <section class="crm-pipeline-board pipeline-stage-board" aria-label="Sales pipeline board">
            @foreach ($stages as $stageKey => $stageName)
                @php
                    $rows = $stageRowsByKey->get($stageKey, []);
                    $items = $stageOpportunities[$stageKey] ?? collect();
                @endphp
                <section class="crm-pipeline-column pipeline-stage-column" data-stage="{{ $stageKey }}">
                    <header class="crm-pipeline-column-head">
                        <div><h2>{{ $stageName }}</h2><span>{{ $rows['count'] ?? 0 }}</span></div>
                        <strong>Rp {{ number_format((float) ($rows['total_value'] ?? 0), 0, ',', '.') }}</strong>
                    </header>

                    <div class="crm-pipeline-deals" data-stage-dropzone>
                        @forelse ($items as $opportunity)
                            @php
                                $probability = min(max((int) $opportunity->probability, 0), 100);
                                $isTerminal = in_array($opportunity->status, ['won', 'lost'], true);
                                $isOverdue = ! $isTerminal && $opportunity->expected_close_date?->lt(today());
                                $isClosingSoon = ! $isTerminal
                                    && $opportunity->expected_close_date
                                    && $opportunity->expected_close_date->gte(today())
                                    && $opportunity->expected_close_date->lte(today()->addDays(14));
                                $isPriority = $isOverdue
                                    || $isClosingSoon
                                    || $probability >= 70
                                    || (float) $opportunity->estimated_value >= 100000000;
                            @endphp
                            <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" draggable="true"
                                data-opportunity-card
                                data-opportunity-id="{{ $opportunity->id }}"
                                data-opportunity-title="{{ $opportunity->title }}"
                                data-update-stage-url="{{ route('admin.sales.opportunities.update-stage', $opportunity) }}"
                                data-edit-url="{{ route('admin.sales.opportunities.edit', $opportunity) }}"
                                @class([
                                'crm-deal-card',
                                'pipeline-opportunity-card',
                                'is-priority' => $isPriority,
                                'is-overdue' => $isOverdue,
                                'is-closing-soon' => $isClosingSoon,
                                'is-won' => $opportunity->status === 'won',
                            ])>
                                <div class="crm-deal-card-title">
                                    <strong>{{ $opportunity->title }}</strong>
                                    @if ($isOverdue)
                                        <span class="pipeline-urgency overdue">Overdue</span>
                                    @elseif ($isClosingSoon)
                                        <span class="pipeline-urgency soon">Closing soon</span>
                                    @elseif ($opportunity->status === 'won')
                                        <span class="pipeline-urgency won">Won</span>
                                    @endif
                                </div>
                                <p>{{ $opportunity->company_name ?: 'No company' }}{{ $opportunity->contact_name ? ' · '.$opportunity->contact_name : '' }}</p>
                                <b>Rp {{ number_format((float) $opportunity->estimated_value, 0, ',', '.') }}</b>
                                <div class="pipeline-probability-row">
                                    <span><i style="width: {{ $probability }}%"></i></span>
                                    <strong>{{ $probability }}%</strong>
                                </div>
                                <div class="crm-deal-card-meta">
                                    <span>{{ $opportunity->expected_close_date?->format('d M Y') ?: 'No close date' }}</span>
                                    <span>{{ $opportunity->assigned_to ?: 'Unassigned' }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="crm-pipeline-empty">Belum ada opportunity di stage ini.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </section>

        <section class="crm-forecast-section pipeline-forecast-section">
            <div class="crm-content-heading"><div><h2>Forecast by Stage</h2><p>Pipeline value and weighted forecast summary.</p></div></div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead><tr><th>Stage</th><th>Opportunity Count</th><th>Total Value</th><th>Average Probability</th><th>Weighted Value</th></tr></thead>
                    <tbody>
                        @foreach ($stageRows as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td>Rp {{ number_format((float) $row['total_value'], 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $row['avg_probability'], 2, ',', '.') }}%</td>
                                <td>Rp {{ number_format((float) $row['weighted_value'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="crm-modal-backdrop" data-stage-confirm-modal hidden>
            <div class="crm-confirm-modal pipeline-stage-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="stage-confirm-title" aria-describedby="stage-confirm-description">
                <span class="crm-confirm-icon pipeline-stage-confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 7h11l-3-3m3 3-3 3M17 17H6l3 3m-3-3 3-3"/></svg>
                </span>
                <h2 id="stage-confirm-title">Pindahkan Opportunity?</h2>
                <p id="stage-confirm-description" data-stage-confirm-message></p>
                <div class="crm-confirm-target"><span>Opportunity</span><strong data-stage-opportunity-title>-</strong></div>
                <div class="crm-confirm-actions">
                    <button type="button" class="btn btn-muted" data-stage-cancel>Batal</button>
                    <button type="button" class="btn btn-primary" data-stage-process>Ya, Proses</button>
                </div>
            </div>
        </div>

        <div class="pipeline-stage-toast" data-stage-toast role="status" aria-live="polite" hidden></div>
    </section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const stageNames = @json($stages);
        const modal = document.querySelector('[data-stage-confirm-modal]');
        const message = document.querySelector('[data-stage-confirm-message]');
        const title = document.querySelector('[data-stage-opportunity-title]');
        const cancelButton = document.querySelector('[data-stage-cancel]');
        const processButton = document.querySelector('[data-stage-process]');
        const toast = document.querySelector('[data-stage-toast]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        let draggedCard = null;
        let pendingMove = null;
        let dragOccurred = false;

        const restoreCard = () => {
            if (!pendingMove) return;
            pendingMove.origin.insertBefore(pendingMove.card, pendingMove.nextSibling);
            pendingMove = null;
        };

        const closeModal = () => {
            modal.hidden = true;
            processButton.disabled = false;
            processButton.textContent = 'Ya, Proses';
        };

        const showToast = (text, type = 'success') => {
            toast.textContent = text;
            toast.className = `pipeline-stage-toast is-${type}`;
            toast.hidden = false;
        };

        document.querySelectorAll('[data-opportunity-card]').forEach((card) => {
            card.addEventListener('dragstart', (event) => {
                draggedCard = card;
                dragOccurred = true;
                card.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.opportunityId);
            });
            card.addEventListener('dragend', () => {
                card.classList.remove('is-dragging');
                draggedCard = null;
                document.querySelectorAll('.is-drag-over').forEach((zone) => zone.classList.remove('is-drag-over'));
                setTimeout(() => { dragOccurred = false; }, 0);
            });
            card.addEventListener('click', (event) => {
                if (dragOccurred) event.preventDefault();
            });
        });

        document.querySelectorAll('[data-stage-dropzone]').forEach((dropzone) => {
            dropzone.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                dropzone.classList.add('is-drag-over');
            });
            dropzone.addEventListener('dragleave', (event) => {
                if (!dropzone.contains(event.relatedTarget)) dropzone.classList.remove('is-drag-over');
            });
            dropzone.addEventListener('drop', (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-drag-over');
                if (!draggedCard) return;

                const destination = dropzone.closest('[data-stage]').dataset.stage;
                const originColumn = draggedCard.closest('[data-stage]');
                if (originColumn.dataset.stage === destination) return;

                pendingMove = {
                    card: draggedCard,
                    origin: draggedCard.parentElement,
                    nextSibling: draggedCard.nextElementSibling,
                    destination,
                };
                dropzone.appendChild(draggedCard);
                message.textContent = `Pindahkan opportunity ke stage ${stageNames[destination]}?`;
                title.textContent = draggedCard.dataset.opportunityTitle;
                modal.hidden = false;
                cancelButton.focus();
            });
        });

        cancelButton.addEventListener('click', () => {
            restoreCard();
            closeModal();
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) cancelButton.click();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden && !processButton.disabled) cancelButton.click();
        });

        processButton.addEventListener('click', async () => {
            if (!pendingMove) return;
            processButton.disabled = true;
            processButton.textContent = 'Memproses...';

            try {
                if (!csrfToken) throw new Error('CSRF token tidak ditemukan. Muat ulang halaman dan coba lagi.');

                const response = await fetch(pendingMove.card.dataset.updateStageUrl, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ status: pendingMove.destination }),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result.message || `Opportunity gagal dipindahkan (${response.status}).`);
                }

                const completedMove = pendingMove;
                pendingMove = null;
                closeModal();
                showToast('Opportunity berhasil dipindahkan.');
                setTimeout(() => {
                    window.location.href = `${completedMove.card.dataset.editUrl}?stage_updated=${encodeURIComponent(completedMove.destination)}`;
                }, 900);
            } catch (error) {
                console.error('Gagal memperbarui stage opportunity:', error);
                restoreCard();
                closeModal();
                showToast(error.message || 'Opportunity gagal dipindahkan.', 'error');
            }
        });
    });
</script>
@endsection
