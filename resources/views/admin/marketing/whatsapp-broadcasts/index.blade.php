@extends('admin.layouts.app')

@section('title', 'WA Blast - Kampanye - Krakatau CRM')

@section('content')
    @php
        $statusLabels = ['draft' => 'DRAFT', 'scheduled' => 'SCHEDULED', 'sending' => 'SENDING', 'completed' => 'COMPLETED', 'failed' => 'FAILED', 'paused' => 'PAUSED', 'cancelled' => 'CANCELLED'];
        $summaryCards = [
            ['label' => 'Total Kampanye', 'value' => $summary['total'] ?? 0, 'tone' => 'blue'],
            ['label' => 'Sedang Berjalan', 'value' => $summary['sending'] ?? 0, 'tone' => 'green'],
            ['label' => 'Terjadwal', 'value' => $summary['scheduled'] ?? 0, 'tone' => 'amber'],
            ['label' => 'Selesai', 'value' => $summary['completed'] ?? 0, 'tone' => 'green'],
            ['label' => 'Draft', 'value' => $summary['draft'] ?? 0, 'tone' => 'gray'],
            ['label' => 'Gagal', 'value' => $summary['failed'] ?? 0, 'tone' => 'red'],
        ];
    @endphp

    <section class="wa-campaign-page">
        <header class="wa-campaign-header">
            <div>
                <span class="wa-breadcrumb">WhatsApp / Kampanye</span>
                <h1>WA Blast - Kampanye</h1>
                <p>Kelola campaign WhatsApp Blast dari template approved, target audience, jadwal, dan tracking pengiriman.</p>
            </div>
            <a href="{{ route('admin.marketing.whatsapp-broadcasts.create') }}" class="wa-btn wa-btn-primary">+ Buat Kampanye</a>
        </header>

        @if (session('success'))<div class="wa-alert success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="wa-alert error">{{ session('error') }}</div>@endif

        <section class="wa-summary-grid">
            @foreach ($summaryCards as $card)
                <article class="wa-summary-card {{ $card['tone'] }}">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ number_format((int) $card['value']) }}</strong>
                </article>
            @endforeach
        </section>

        <article class="wa-list-card">
            <div class="wa-list-head">
                <div>
                    <h2>Daftar Kampanye</h2>
                    <p>Progress dihitung dari recipient yang tersimpan di campaign.</p>
                </div>
            </div>

            @if ($broadcasts->isEmpty())
                <div class="wa-empty-state">
                    <strong>Belum ada kampanye WhatsApp</strong>
                    <span>Buat campaign pertama untuk mulai WA Blast ke customer atau lead.</span>
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.create') }}" class="wa-btn wa-btn-primary">Buat Kampanye</a>
                </div>
            @else
                <div class="wa-table-wrap">
                    <table class="wa-campaign-table">
                        <thead>
                            <tr>
                                <th>Kampanye</th>
                                <th>Template</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Biaya</th>
                                <th>Tanggal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($broadcasts as $broadcast)
                                @php
                                    $total = max(1, (int) ($broadcast->total_recipients ?: $broadcast->recipients_count));
                                    $done = (int) $broadcast->sent_count + (int) $broadcast->failed_count;
                                    $progress = min(100, round(($done / $total) * 100));
                                    $cost = ((int) ($broadcast->total_recipients ?: $broadcast->recipients_count)) * 350;
                                @endphp
                                <tr>
                                    <td>
                                        <a class="wa-campaign-name" href="{{ route('admin.marketing.whatsapp-broadcasts.show', $broadcast) }}">{{ $broadcast->name }}</a>
                                        <small>{{ ucfirst($broadcast->target_type) }} audience</small>
                                    </td>
                                    <td>
                                        <strong>{{ $broadcast->messageTemplate?->name ?: '-' }}</strong>
                                        <small>{{ $broadcast->messageTemplate ? $broadcast->messageTemplate->category.' / '.$broadcast->messageTemplate->language : Str::limit($broadcast->message_template, 38) }}</small>
                                    </td>
                                    <td><span class="wa-status status-{{ $broadcast->status }}">{{ $statusLabels[$broadcast->status] ?? strtoupper($broadcast->status) }}</span></td>
                                    <td>
                                        <div class="wa-progress"><span style="width: {{ $progress }}%"></span></div>
                                        <div class="wa-progress-meta">
                                            <span>{{ number_format((int) $broadcast->sent_count) }} terkirim</span>
                                            <span>{{ number_format((int) $broadcast->failed_count) }} gagal</span>
                                            <span>{{ number_format((int) $broadcast->delivered_count) }} delivered</span>
                                            <span>{{ number_format((int) $broadcast->read_count) }} read</span>
                                        </div>
                                    </td>
                                    <td><strong>Rp{{ number_format($cost, 0, ',', '.') }}</strong></td>
                                    <td>{{ ($broadcast->scheduled_at ?: $broadcast->created_at)?->format('d M Y H:i') ?: '-' }}</td>
                                    <td><a href="{{ route('admin.marketing.whatsapp-broadcasts.show', $broadcast) }}" class="wa-btn wa-btn-sm wa-btn-secondary">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="wa-pagination">{{ $broadcasts->links() }}</div>
            @endif
        </article>
    </section>

    <style>
        .wa-campaign-page{display:grid;gap:1rem}.wa-campaign-header,.wa-list-card,.wa-summary-card{border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 8px 24px rgba(47,43,61,.07)}.wa-campaign-header{display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:1.15rem}.wa-breadcrumb{color:#6f6b7d;font-weight:800;font-size:.78rem}.wa-campaign-header h1{margin:.15rem 0;color:#2f2b3d}.wa-campaign-header p,.wa-list-head p{margin:0;color:#6f6b7d}.wa-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid rgba(47,43,61,.12);font-weight:800;text-decoration:none;cursor:pointer}.wa-btn-primary{background:#28c76f;color:#fff;border-color:#28c76f}.wa-btn-secondary{background:#fff;color:#4b465c}.wa-btn-sm{min-height:2rem;padding:.35rem .65rem;font-size:.8rem}.wa-alert{padding:.8rem 1rem;border-radius:8px;background:#fff;border:1px solid rgba(47,43,61,.1)}.wa-alert.success{background:#f0fbf5;color:#168a49}.wa-alert.error{background:#fff5f5;color:#b42324}.wa-summary-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.85rem}.wa-summary-card{padding:1rem;border-left:4px solid #d9d8de}.wa-summary-card span{color:#6f6b7d;font-weight:700}.wa-summary-card strong{display:block;margin-top:.25rem;color:#2f2b3d;font-size:1.45rem}.wa-summary-card.green{border-left-color:#28c76f}.wa-summary-card.amber{border-left-color:#ffb547}.wa-summary-card.red{border-left-color:#ea5455}.wa-summary-card.blue{border-left-color:#2f80ed}.wa-list-card{overflow:hidden}.wa-list-head{padding:1rem 1.1rem;border-bottom:1px solid rgba(47,43,61,.08)}.wa-list-head h2{margin:0;color:#2f2b3d}.wa-table-wrap{overflow-x:auto}.wa-campaign-table{width:100%;border-collapse:collapse;min-width:920px}.wa-campaign-table th,.wa-campaign-table td{padding:.9rem 1.05rem;border-bottom:1px solid rgba(47,43,61,.08);vertical-align:middle;text-align:left}.wa-campaign-table th{background:#f8f7fb;color:#6f6b7d;font-size:.76rem;text-transform:uppercase}.wa-campaign-name{display:block;color:#2f2b3d;font-weight:900;text-decoration:none}.wa-campaign-table small{display:block;margin-top:.2rem;color:#6f6b7d}.wa-status{display:inline-flex;border-radius:999px;padding:.32rem .65rem;font-size:.74rem;font-weight:900}.status-draft{background:#f1f1f2;color:#4b465c}.status-scheduled{background:#fff4e5;color:#a35a00}.status-sending{background:#e8f8ef;color:#168a49}.status-completed{background:#e8f8ef;color:#168a49}.status-failed{background:#fff0f0;color:#c23a3b}.wa-progress{height:.5rem;border-radius:999px;background:#eef0f2;overflow:hidden}.wa-progress span{display:block;height:100%;background:#28c76f}.wa-progress-meta{display:flex;gap:.55rem;flex-wrap:wrap;margin-top:.35rem;color:#6f6b7d;font-size:.76rem}.wa-empty-state{display:grid;justify-items:center;gap:.5rem;padding:2.5rem;text-align:center;color:#6f6b7d}.wa-empty-state strong{color:#2f2b3d}.wa-pagination{padding:1rem}@media(max-width:1100px){.wa-summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:720px){.wa-campaign-header{display:grid}.wa-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.wa-btn{width:100%}}
    </style>
@endsection
