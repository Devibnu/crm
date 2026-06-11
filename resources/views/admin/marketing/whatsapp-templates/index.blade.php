@extends('admin.layouts.app')

@section('title', 'Template Pesan WhatsApp - Krakatau CRM')

@section('content')
    @php
        $statusLabels = [
            'APPROVED' => 'Approved Meta',
            'PENDING' => 'Pending',
            'REJECTED' => 'Rejected',
            'DRAFT' => 'Draft',
            'NOT_FOUND_ON_META' => 'Missing on Meta',
        ];
        $summary = $summary ?? ['total' => $templates->total(), 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'missing_on_meta' => 0];
        $summaryCards = [
            ['label' => 'Total Template', 'value' => $summary['total'] ?? 0, 'tone' => 'total'],
            ['label' => 'Approved Meta', 'value' => $summary['approved'] ?? 0, 'tone' => 'approved'],
            ['label' => 'Pending', 'value' => $summary['pending'] ?? 0, 'tone' => 'pending'],
            ['label' => 'Rejected', 'value' => $summary['rejected'] ?? 0, 'tone' => 'rejected'],
            ['label' => 'Missing on Meta', 'value' => $summary['missing_on_meta'] ?? 0, 'tone' => 'missing'],
        ];
    @endphp

    <section class="wa-template-page">
        <div class="wa-template-header">
            <div>
                <h1>Template Pesan WhatsApp</h1>
                <p>Kelola template pesan WhatsApp untuk broadcast dan follow-up customer</p>
            </div>
            <div class="wa-header-actions">
                <a href="{{ route('admin.marketing.whatsapp-templates.create') }}" class="wa-btn wa-btn-primary">+ Tambah Template</a>
                <form method="POST" action="{{ route('admin.marketing.whatsapp-templates.sync') }}">
                    @csrf
                    <button class="wa-btn wa-btn-secondary" type="submit">Sync Templates</button>
                </form>
            </div>
        </div>

        @if (session('success'))<div class="wa-alert success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="wa-alert error">{{ session('error') }}</div>@endif

        <article class="wa-guide-card">
            <div>
                <span>WA Blast Guide</span>
                <h2>Cara Menggunakan Template untuk WA Blast</h2>
            </div>
            <ol>
                <li>Buat template baru</li>
                <li>Submit ke Meta untuk review</li>
                <li>Tunggu approval Meta</li>
                <li>Template approved otomatis bisa dipakai di WA Blast</li>
                <li>Klik Sync Templates untuk refresh status dari Meta</li>
            </ol>
        </article>

        <div class="wa-summary-grid">
            @foreach ($summaryCards as $card)
                <article class="wa-summary-card {{ $card['tone'] }}">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ number_format((int) $card['value']) }}</strong>
                </article>
            @endforeach
        </div>

        @if (! $provider)
            <article class="wa-empty-panel">
                <strong>Hubungkan WhatsApp Business Cloud API terlebih dahulu.</strong>
                <span>Setelah provider Meta aktif, template bisa dibuat, disinkronkan, dan diuji dari CRM.</span>
            </article>
        @elseif ($templates->isEmpty())
            <article class="wa-empty-panel">
                <strong>Belum ada template WhatsApp.</strong>
                <span>Buat template pertama atau sync template yang sudah ada di Meta.</span>
                <a href="{{ route('admin.marketing.whatsapp-templates.create') }}" class="wa-btn wa-btn-primary">+ Tambah Template</a>
            </article>
        @else
            <div class="wa-template-grid">
                @foreach ($templates as $template)
                    @php
                        $status = strtoupper((string) $template->status);
                        $bodyText = $template->body ?: $template->body_meta ?: '-';
                        $isAvailableOnMeta = $template->isAvailableForMetaUse();
                        $isMissingOnMeta = $template->isMissingOnMeta();
                        $category = strtoupper((string) ($template->category ?: '-'));
                        $language = strtoupper((string) ($template->language ?: '-'));
                    @endphp
                    <article @class(['wa-template-card', 'is-missing' => $isMissingOnMeta])>
                        <div class="wa-card-top">
                            <div class="wa-card-title">
                                <h2>{{ $template->name }}</h2>
                                @if ($template->is_default && $isAvailableOnMeta)
                                    <span class="wa-default-pill">Default</span>
                                @endif
                            </div>
                            <span class="wa-status-badge status-{{ strtolower($status ?: 'draft') }}">{{ $statusLabels[$status] ?? ($template->status ?: '-') }}</span>
                        </div>

                        <div class="wa-badge-row">
                            <span class="wa-chip category-{{ strtolower($category) }}">{{ $category }}</span>
                            <span class="wa-chip language">{{ $language }}</span>
                        </div>

                        <p class="wa-template-body">{{ $bodyText }}</p>

                        <div class="wa-card-footer">
                            <span>Status disinkron dari Meta: <strong>{{ $template->name }}</strong></span>
                            <span>Last synced: {{ $template->last_synced_at?->format('d M Y H:i') ?: '-' }}</span>
                        </div>

                        <div class="wa-card-actions">
                            <a href="{{ route('admin.marketing.whatsapp-templates.show', $template) }}" class="wa-card-link primary">View Detail</a>
                            <button class="wa-card-link js-send-test-template" type="button" data-url="{{ route('admin.marketing.whatsapp-templates.send-test', $template) }}" @disabled(! $isAvailableOnMeta)>Send Test</button>
                            <a href="{{ route('admin.marketing.whatsapp-templates.edit', $template) }}" class="wa-card-link">Edit</a>
                            <details class="wa-more-menu">
                                <summary>More</summary>
                                <div class="wa-more-panel">
                                    @if ($isAvailableOnMeta)
                                        <form method="POST" action="{{ route('admin.marketing.whatsapp-templates.default', $template) }}">
                                            @csrf
                                            <button type="submit">Set Default</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.marketing.whatsapp-templates.destroy', $template) }}" onsubmit="return confirm('Hapus template ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="wa-pagination-wrap">
                {{ $templates->links() }}
            </div>
        @endif

        <pre id="whatsapp-template-test-result" class="wa-alert" style="display:none;white-space:pre-wrap;"></pre>
    </section>

    <script>
        document.querySelectorAll('.js-send-test-template').forEach((button) => {
            button.addEventListener('click', async () => {
                const phone = window.prompt('Nomor tujuan test', '6281234567890');
                const resultBox = document.getElementById('whatsapp-template-test-result');
                if (!phone || !resultBox) return;

                resultBox.style.display = 'block';
                resultBox.classList.remove('success', 'error');
                resultBox.textContent = 'Sending...';

                try {
                    const response = await fetch(button.dataset.url, {
                        method: 'POST',
                        headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        body: JSON.stringify({phone}),
                    });
                    const payload = await response.json();
                    resultBox.classList.toggle('success', response.ok);
                    resultBox.classList.toggle('error', !response.ok);
                    resultBox.textContent = JSON.stringify(payload, null, 2);
                } catch (error) {
                    resultBox.classList.add('error');
                    resultBox.textContent = error.message || 'Gagal mengirim test template.';
                }
            });
        });
    </script>

    <style>
        .wa-template-page{display:grid;gap:1rem}.wa-template-header{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.1rem;border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 7px 24px rgba(47,43,61,.06)}.wa-template-header h1{margin:0;color:#2f2b3d;font-size:1.42rem;line-height:1.2}.wa-template-header p{margin:.25rem 0 0;color:#6f6b7d}.wa-header-actions{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}.wa-header-actions form{margin:0}.wa-btn,.wa-card-link{display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid rgba(47,43,61,.12);font-weight:800;text-decoration:none;cursor:pointer}.wa-btn{min-height:2.35rem;padding:.5rem .9rem}.wa-btn-primary,.wa-card-link.primary{background:#28c76f;border-color:#28c76f;color:#fff}.wa-btn-secondary{background:#fff;color:#4b465c}.wa-alert{padding:.8rem 1rem;border-radius:8px;background:#fff;border:1px solid rgba(47,43,61,.1);box-shadow:0 5px 18px rgba(47,43,61,.05)}.wa-alert.success{background:#f0fbf5;color:#168a49;border-color:rgba(40,199,111,.22)}.wa-alert.error{background:#fff5f5;color:#b42324;border-color:rgba(234,84,85,.24)}.wa-guide-card{display:grid;grid-template-columns:minmax(220px,.75fr) minmax(0,1.25fr);gap:1rem;padding:1rem 1.1rem;border-radius:8px;background:linear-gradient(135deg,#7367f0,#28c76f);color:#fff;box-shadow:0 10px 28px rgba(115,103,240,.2)}.wa-guide-card span{display:block;font-size:.72rem;font-weight:900;text-transform:uppercase;opacity:.82}.wa-guide-card h2{margin:.18rem 0 0;font-size:1.08rem}.wa-guide-card ol{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.5rem;margin:0;padding:0;list-style:none}.wa-guide-card li{min-height:3rem;padding:.55rem .65rem;border-radius:8px;background:rgba(255,255,255,.16);font-size:.78rem;font-weight:800;line-height:1.25}.wa-summary-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.75rem}.wa-summary-card{min-height:4.4rem;padding:.75rem .85rem;border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 6px 18px rgba(47,43,61,.045)}.wa-summary-card span{display:block;color:#6f6b7d;font-size:.75rem;font-weight:800}.wa-summary-card strong{display:block;margin-top:.25rem;color:#2f2b3d;font-size:1.35rem}.wa-summary-card.approved{border-color:rgba(40,199,111,.22)}.wa-summary-card.pending{border-color:rgba(255,159,67,.28)}.wa-summary-card.rejected{border-color:rgba(234,84,85,.24)}.wa-summary-card.missing{background:#f8f8fa}.wa-template-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.wa-template-card{display:grid;gap:.72rem;min-width:0;padding:.9rem;border:1px solid rgba(47,43,61,.1);border-radius:8px;background:#fff;box-shadow:0 8px 20px rgba(47,43,61,.055)}.wa-template-card.is-missing{background:#f7f7f9;border-color:rgba(111,107,125,.22);box-shadow:none}.wa-card-top{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.65rem;align-items:start}.wa-card-title{display:flex;align-items:center;gap:.4rem;min-width:0}.wa-card-title h2{margin:0;color:#2f2b3d;font-size:.98rem;line-height:1.25;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.wa-default-pill{border-radius:999px;background:#eef6ff;color:#1677c6;padding:.22rem .45rem;font-size:.66rem;font-weight:900}.wa-badge-row{display:flex;gap:.4rem;flex-wrap:wrap}.wa-chip,.wa-status-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:.28rem .55rem;font-size:.68rem;font-weight:900;line-height:1;white-space:nowrap}.wa-chip{background:#f1f1f2;color:#4b465c}.wa-chip.category-marketing{background:#fff4e5;color:#b76600}.wa-chip.category-utility{background:#eef6ff;color:#1677c6}.wa-chip.category-authentication{background:#f4f1ff;color:#7367f0}.wa-chip.language{background:#f8f8fa;color:#6f6b7d}.wa-status-badge.status-approved{background:#e8f8ef;color:#168a49}.wa-status-badge.status-pending{background:#fff4e5;color:#b76600}.wa-status-badge.status-rejected{background:#fff0f0;color:#c23a3b}.wa-status-badge.status-not_found_on_meta{background:#ececef;color:#5d596c}.wa-template-body{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;min-height:3.45rem;margin:0;color:#4b465c;font-size:.84rem;line-height:1.36;overflow:hidden}.wa-card-footer{display:grid;gap:.18rem;padding-top:.55rem;border-top:1px solid rgba(47,43,61,.08);color:#8b8698;font-size:.72rem}.wa-card-footer strong{color:#5d596c}.wa-card-actions{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap}.wa-card-link{min-height:2rem;padding:.38rem .58rem;background:#fff;color:#4b465c;font-size:.78rem}.wa-card-link:disabled{opacity:.48;cursor:not-allowed}.wa-more-menu{position:relative;margin-left:auto}.wa-more-menu summary{list-style:none;display:inline-flex;align-items:center;justify-content:center;min-height:2rem;padding:.38rem .58rem;border-radius:8px;border:1px solid rgba(47,43,61,.12);background:#fff;color:#4b465c;font-size:.78rem;font-weight:900;cursor:pointer}.wa-more-menu summary::-webkit-details-marker{display:none}.wa-more-panel{position:absolute;right:0;top:calc(100% + .4rem);z-index:10;display:grid;min-width:10rem;padding:.35rem;border:1px solid rgba(47,43,61,.1);border-radius:8px;background:#fff;box-shadow:0 12px 30px rgba(47,43,61,.16)}.wa-more-panel button{width:100%;display:flex;border:0;border-radius:6px;background:transparent;color:#4b465c;padding:.58rem .65rem;font-weight:800;text-align:left;cursor:pointer}.wa-more-panel button:hover{background:#f6f6f8}.wa-more-panel form{margin:0}.wa-more-panel .danger{color:#c23a3b}.wa-empty-panel{display:grid;justify-items:center;gap:.55rem;padding:2rem;text-align:center;color:#6f6b7d;border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 7px 24px rgba(47,43,61,.06)}.wa-empty-panel strong{color:#2f2b3d}.wa-pagination-wrap{display:flex;justify-content:center}@media(max-width:1180px){.wa-template-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.wa-guide-card ol{grid-template-columns:repeat(3,minmax(0,1fr))}.wa-summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:860px){.wa-template-header,.wa-guide-card{grid-template-columns:1fr}.wa-template-header{display:grid}.wa-header-actions,.wa-header-actions form,.wa-btn{width:100%}.wa-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.wa-template-grid{grid-template-columns:1fr}.wa-guide-card ol{grid-template-columns:1fr}.wa-card-actions{display:grid;grid-template-columns:1fr 1fr auto}.wa-card-link{width:100%}}@media(max-width:560px){.wa-summary-grid{grid-template-columns:1fr}.wa-card-top{grid-template-columns:1fr}.wa-more-menu{margin-left:0}.wa-more-menu summary{width:100%}.wa-more-panel{position:static;margin-top:.4rem}.wa-card-actions{grid-template-columns:1fr}}
    </style>
@endsection
