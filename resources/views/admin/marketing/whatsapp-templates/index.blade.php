@extends('admin.layouts.app')

@section('title', 'WhatsApp Templates - Krakatau CRM')

@section('content')
    @php
        $labels = ['APPROVED' => 'Disetujui', 'PENDING' => 'Sedang Ditinjau', 'REJECTED' => 'Ditolak', 'DRAFT' => 'Draft', 'NOT_FOUND_ON_META' => 'Missing on Meta'];
        $summary = $summary ?? ['total' => $templates->total(), 'approved' => 0, 'pending' => 0, 'rejected' => 0];
        $summaryCards = [
            ['label' => 'Total Templates', 'value' => $summary['total'] ?? 0, 'tone' => 'total', 'icon' => 'layers'],
            ['label' => 'Approved', 'value' => $summary['approved'] ?? 0, 'tone' => 'approved', 'icon' => 'check'],
            ['label' => 'Pending', 'value' => $summary['pending'] ?? 0, 'tone' => 'pending', 'icon' => 'clock'],
            ['label' => 'Rejected', 'value' => $summary['rejected'] ?? 0, 'tone' => 'rejected', 'icon' => 'x'],
        ];
    @endphp

    <section class="wa-template-gallery">
        <div class="wa-gallery-toolbar">
            <div class="wa-gallery-title">
                <span class="wa-kicker">Marketing Automation</span>
                <h1>WhatsApp Templates</h1>
                <p>Kelola template pesan sebagai gallery siap pakai untuk broadcast dan campaign WhatsApp Cloud API.</p>
            </div>
            <div class="wa-gallery-actions">
                <a href="{{ route('admin.marketing.whatsapp-templates.create') }}" class="wa-action-btn primary">Tambah Template</a>
                <form method="POST" action="{{ route('admin.marketing.whatsapp-templates.sync') }}">
                    @csrf
                    <button class="wa-action-btn secondary" type="submit">Sync Templates</button>
                </form>
            </div>
        </div>

        @if (session('success'))<div class="wa-gallery-alert success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="wa-gallery-alert error">{{ session('error') }}</div>@endif

        <div class="wa-summary-grid">
            @foreach ($summaryCards as $card)
                <article class="wa-summary-card {{ $card['tone'] }}">
                    <div class="wa-summary-avatar">
                        @if ($card['icon'] === 'check')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        @elseif ($card['icon'] === 'clock')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg>
                        @elseif ($card['icon'] === 'x')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h14v14H7z"/><path d="M3 3h14v14"/></svg>
                        @endif
                    </div>
                    <div>
                        <span>{{ $card['label'] }}</span>
                        <strong>{{ number_format((int) $card['value']) }}</strong>
                    </div>
                </article>
            @endforeach
        </div>

        @if (! $provider)
            <article class="wa-empty-panel">
                <div class="wa-empty-icon">WA</div>
                <strong>Hubungkan WhatsApp Business Cloud API terlebih dahulu.</strong>
                <span>Setelah provider Meta aktif, template bisa dibuat, disinkronkan, dan diuji dari CRM.</span>
            </article>
        @elseif ($templates->isEmpty())
            <article class="wa-empty-panel">
                <div class="wa-empty-icon">WA</div>
                <strong>Belum ada template WhatsApp.</strong>
                <span>Buat template pertama atau sync template yang sudah ada di Meta.</span>
                <a href="{{ route('admin.marketing.whatsapp-templates.create') }}" class="wa-action-btn primary">Tambah Template</a>
            </article>
        @else
            <div class="wa-template-grid">
                @foreach ($templates as $template)
                    @php
                        $status = strtoupper((string) $template->status);
                        $bodyText = $template->body ?: $template->body_meta ?: '-';
                        $previewHeader = $template->header ?: null;
                        $previewFooter = $template->footer ?: null;
                        $isAvailableOnMeta = $template->isAvailableForMetaUse();
                        $isMissingOnMeta = $template->isMissingOnMeta();
                    @endphp
                    <article class="wa-template-card status-{{ strtolower($status ?: 'draft') }}">
                        <div class="wa-card-head">
                            <div class="wa-card-identity">
                                <div class="wa-template-avatar">{{ strtoupper(substr($template->name, 0, 2)) }}</div>
                                <div>
                                    <span class="wa-template-source">{{ $template->source ?: 'meta_sync' }}</span>
                                    <h2>{{ $template->name }}</h2>
                                </div>
                            </div>
                            <div class="wa-card-badges">
                                <span class="wa-status-badge {{ strtolower($status ?: 'draft') }}">{{ $labels[$status] ?? ($template->status ?: '-') }}</span>
                                @if ($isMissingOnMeta)
                                    <span class="wa-status-badge missing">Missing on Meta</span>
                                    <span class="wa-status-badge archived">Archived</span>
                                @elseif ($template->source === 'meta_sync')
                                    <span class="wa-status-badge synced">Synced</span>
                                @endif
                                @if ($template->is_default)
                                    <span class="wa-default-badge">Active</span>
                                @endif
                            </div>
                        </div>

                        <div class="wa-preview-panel">
                            <div class="wa-preview-device">
                                <div class="wa-preview-bar">
                                    <span></span><span></span><span></span>
                                </div>
                                <div class="wa-preview-bubble">
                                    @if ($previewHeader)
                                        <strong>{{ $previewHeader }}</strong>
                                    @endif
                                    <p>{{ Str::limit($bodyText, 190) }}</p>
                                    @if ($previewFooter)
                                        <small>{{ $previewFooter }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="wa-card-meta">
                            <div>
                                <span>Kategori</span>
                                <strong>{{ $template->category ?: '-' }}</strong>
                            </div>
                            <div>
                                <span>Bahasa</span>
                                <strong>{{ strtoupper($template->language ?: '-') }}</strong>
                            </div>
                            <div>
                                <span>Last synced</span>
                                <strong>{{ $template->last_synced_at?->format('d M Y H:i') ?: '-' }}</strong>
                            </div>
                        </div>

                        <div class="wa-card-actions">
                            <a href="{{ route('admin.marketing.whatsapp-templates.show', $template) }}" class="wa-card-btn primary">View Detail</a>
                            <button class="wa-card-btn secondary js-send-test-template" data-url="{{ route('admin.marketing.whatsapp-templates.send-test', $template) }}" @disabled(! $isAvailableOnMeta)>Send Test</button>
                            <details class="wa-more-menu">
                                <summary>More</summary>
                                <div class="wa-more-panel">
                                    <a href="{{ route('admin.marketing.whatsapp-templates.edit', $template) }}">Edit</a>
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

        <pre id="whatsapp-template-test-result" class="wa-gallery-alert" style="display:none;white-space:pre-wrap;"></pre>
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
        .wa-template-gallery{display:grid;gap:1rem}.wa-gallery-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.1rem 1.2rem;border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 7px 24px rgba(47,43,61,.06)}.wa-gallery-title{min-width:0}.wa-kicker,.wa-template-source{display:inline-flex;color:#6f6b7d;font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:0}.wa-gallery-title h1{margin:.16rem 0;color:#2f2b3d;font-size:1.48rem;line-height:1.2}.wa-gallery-title p{margin:0;color:#6f6b7d;max-width:48rem}.wa-gallery-actions{display:flex;gap:.65rem;align-items:center;flex-wrap:wrap}.wa-gallery-actions form{margin:0}.wa-action-btn,.wa-card-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid rgba(47,43,61,.12);font-weight:800;text-decoration:none;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,background .18s ease}.wa-action-btn{min-height:2.45rem;padding:.55rem .95rem}.wa-action-btn.primary,.wa-card-btn.primary{background:#28c76f;color:#fff;border-color:#28c76f;box-shadow:0 4px 12px rgba(40,199,111,.22)}.wa-action-btn.secondary,.wa-card-btn.secondary{background:#fff;color:#4b465c}.wa-action-btn:hover,.wa-card-btn:hover{transform:translateY(-1px);text-decoration:none}.wa-gallery-alert{padding:.85rem 1rem;border-radius:8px;background:#fff;border:1px solid rgba(47,43,61,.1);box-shadow:0 5px 18px rgba(47,43,61,.05)}.wa-gallery-alert.success{background:#f0fbf5;color:#168a49;border-color:rgba(40,199,111,.22)}.wa-gallery-alert.error{background:#fff5f5;color:#b42324;border-color:rgba(234,84,85,.24)}.wa-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.wa-summary-card{display:flex;align-items:center;gap:.85rem;min-height:6rem;padding:1rem;border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 7px 20px rgba(47,43,61,.055)}.wa-summary-avatar{display:grid;place-items:center;width:2.75rem;height:2.75rem;border-radius:8px;background:#f1f1f2;color:#4b465c;flex:0 0 auto}.wa-summary-avatar svg{width:1.25rem;height:1.25rem;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.wa-summary-card span{display:block;color:#6f6b7d;font-weight:700;font-size:.82rem}.wa-summary-card strong{display:block;margin-top:.15rem;color:#2f2b3d;font-size:1.55rem;line-height:1}.wa-summary-card.approved .wa-summary-avatar{background:#e8f8ef;color:#168a49}.wa-summary-card.pending .wa-summary-avatar{background:#fff4e5;color:#b76600}.wa-summary-card.rejected .wa-summary-avatar{background:#fff0f0;color:#c23a3b}.wa-summary-card.total .wa-summary-avatar{background:#eef6ff;color:#1677c6}.wa-template-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.wa-template-card{display:grid;grid-template-rows:auto auto 1fr auto;gap:.9rem;padding:1rem;border:1px solid rgba(47,43,61,.1);border-left-width:4px;border-radius:8px;background:#fff;box-shadow:0 10px 24px rgba(47,43,61,.065);min-width:0}.wa-template-card.status-approved{border-left-color:#28c76f}.wa-template-card.status-pending{border-left-color:#ffb547}.wa-template-card.status-rejected{border-left-color:#ea5455}.wa-template-card.status-draft{border-left-color:#6f6b7d}.wa-template-card.status-not_found_on_meta{border-left-color:#6f6b7d}.wa-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}.wa-card-identity{display:flex;gap:.75rem;align-items:flex-start;min-width:0}.wa-template-avatar{display:grid;place-items:center;width:2.6rem;height:2.6rem;border-radius:8px;background:#e8f8ef;color:#168a49;font-weight:900;flex:0 0 auto}.wa-template-card h2{margin:.1rem 0 0;color:#2f2b3d;font-size:1.04rem;line-height:1.25;overflow-wrap:anywhere}.wa-card-badges{display:grid;gap:.35rem;justify-items:end;flex:0 0 auto}.wa-status-badge,.wa-default-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:.35rem .72rem;font-size:.74rem;font-weight:900;line-height:1;white-space:nowrap}.wa-status-badge.approved{background:#e8f8ef;color:#168a49}.wa-status-badge.pending{background:#fff4e5;color:#b76600}.wa-status-badge.rejected{background:#fff0f0;color:#c23a3b}.wa-status-badge.draft{background:#f1f1f2;color:#4b465c}.wa-status-badge.not_found_on_meta,.wa-status-badge.missing,.wa-status-badge.archived{background:#f1f1f2;color:#4b465c}.wa-status-badge.synced{background:#eef6ff;color:#1677c6}.wa-default-badge{background:#eef6ff;color:#1677c6}.wa-preview-panel{padding:.75rem;border-radius:8px;background:linear-gradient(135deg,#eff7f1,#eef6ff);border:1px solid rgba(47,43,61,.06)}.wa-preview-device{display:grid;gap:.55rem}.wa-preview-bar{display:flex;gap:.25rem}.wa-preview-bar span{width:.38rem;height:.38rem;border-radius:999px;background:rgba(75,70,92,.28)}.wa-preview-bubble{position:relative;max-width:92%;padding:.72rem .8rem;border-radius:8px 8px 8px 2px;background:#fff;color:#2f2b3d;box-shadow:0 6px 16px rgba(47,43,61,.09)}.wa-preview-bubble strong{display:block;margin-bottom:.38rem;font-size:.86rem}.wa-preview-bubble p{margin:0;color:#4b465c;line-height:1.45;white-space:pre-wrap;overflow-wrap:anywhere}.wa-preview-bubble small{display:block;margin-top:.45rem;color:#8b8698}.wa-card-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem}.wa-card-meta div{padding:.62rem .68rem;border-radius:8px;background:#f8f8fa;min-width:0}.wa-card-meta span{display:block;color:#8b8698;font-size:.72rem;font-weight:800}.wa-card-meta strong{display:block;margin-top:.18rem;color:#2f2b3d;font-size:.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.wa-card-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}.wa-card-btn{min-height:2.25rem;padding:.42rem .72rem;font-size:.82rem}.wa-card-btn:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none}.wa-more-menu{position:relative;margin-left:auto}.wa-more-menu summary{list-style:none;display:inline-flex;align-items:center;justify-content:center;min-height:2.25rem;padding:.42rem .72rem;border-radius:8px;border:1px solid rgba(47,43,61,.12);background:#fff;color:#4b465c;font-size:.82rem;font-weight:900;cursor:pointer}.wa-more-menu summary::-webkit-details-marker{display:none}.wa-more-panel{position:absolute;right:0;top:calc(100% + .45rem);z-index:10;display:grid;min-width:10.5rem;padding:.35rem;border:1px solid rgba(47,43,61,.1);border-radius:8px;background:#fff;box-shadow:0 12px 30px rgba(47,43,61,.16)}.wa-more-panel a,.wa-more-panel button{width:100%;display:flex;align-items:center;border:0;border-radius:6px;background:transparent;color:#4b465c;padding:.62rem .7rem;font-weight:800;text-align:left;text-decoration:none;cursor:pointer}.wa-more-panel a:hover,.wa-more-panel button:hover{background:#f6f6f8}.wa-more-panel form{margin:0}.wa-more-panel .danger{color:#c23a3b}.wa-empty-panel{display:grid;justify-items:center;gap:.55rem;padding:2rem;text-align:center;color:#6f6b7d;border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 7px 24px rgba(47,43,61,.06)}.wa-empty-panel strong{color:#2f2b3d}.wa-empty-icon{display:grid;place-items:center;width:3rem;height:3rem;border-radius:8px;background:#e8f8ef;color:#168a49;font-weight:900}.wa-pagination-wrap{display:flex;justify-content:center}@media(max-width:1180px){.wa-template-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:860px){.wa-gallery-toolbar{display:grid}.wa-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.wa-template-grid{grid-template-columns:1fr}.wa-gallery-actions,.wa-gallery-actions form,.wa-action-btn{width:100%}.wa-card-actions{display:grid;grid-template-columns:1fr 1fr auto}.wa-card-btn{width:100%}}@media(max-width:560px){.wa-summary-grid,.wa-card-meta{grid-template-columns:1fr}.wa-card-head{display:grid}.wa-card-badges{justify-items:start}.wa-card-actions{grid-template-columns:1fr}.wa-more-menu{margin-left:0}.wa-more-menu summary{width:100%}.wa-more-panel{position:static;margin-top:.45rem}.wa-preview-bubble{max-width:100%}}
    </style>
@endsection
