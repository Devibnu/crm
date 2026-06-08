@extends('admin.layouts.app')

@section('title', 'WhatsApp Templates - Krakatau CRM')

@section('content')
    @php($labels = ['APPROVED' => 'Disetujui', 'PENDING' => 'Sedang ditinjau', 'REJECTED' => 'Ditolak', 'DRAFT' => 'Draft'])
    <section class="wa-template-hub">
        <div class="wa-hub-header">
            <div>
                <span>Marketing Automation</span>
                <h1>WhatsApp Templates</h1>
                <p>Template akan dikirim ke Meta untuk ditinjau. Biasanya membutuhkan beberapa menit hingga beberapa jam.</p>
            </div>
            <div class="wa-hub-actions">
                <a href="{{ route('admin.marketing.whatsapp-templates.create') }}" class="wa-hub-btn primary">Tambah Template</a>
                <form method="POST" action="{{ route('admin.marketing.whatsapp-templates.sync') }}">
                    @csrf
                    <button class="wa-hub-btn secondary" type="submit">Sync Templates</button>
                </form>
            </div>
        </div>

        @if (session('success'))<div class="wa-hub-alert success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="wa-hub-alert error">{{ session('error') }}</div>@endif

        @if (! $provider)
            <article class="wa-empty-panel">
                <strong>Hubungkan WhatsApp Business Cloud API terlebih dahulu.</strong>
                <span>Setelah provider Meta aktif, Anda bisa membuat dan submit template dari CRM.</span>
            </article>
        @elseif ($templates->isEmpty())
            <article class="wa-empty-panel">
                <strong>Belum ada template WhatsApp.</strong>
                <span>Buat template pertama atau sync template yang sudah ada di Meta.</span>
                <a href="{{ route('admin.marketing.whatsapp-templates.create') }}" class="wa-hub-btn primary">Tambah Template</a>
            </article>
        @else
            <div class="wa-template-card-grid">
                @foreach ($templates as $template)
                    <article class="wa-template-card">
                        <div class="wa-template-card-top">
                            <div>
                                <h2>{{ $template->name }}</h2>
                                <p>{{ Str::limit($template->body ?: $template->body_meta ?: '-', 110) }}</p>
                            </div>
                            <span class="wa-status wa-status-{{ strtolower((string) $template->status) }}">{{ $labels[$template->status] ?? ($template->status ?: '-') }}</span>
                        </div>
                        <div class="wa-template-meta">
                            <span>{{ $template->category ?: '-' }}</span>
                            <span>{{ strtoupper($template->language ?: '-') }}</span>
                            <span>{{ $template->source ?: 'meta_sync' }}</span>
                            <span>{{ $template->last_synced_at?->format('d M Y H:i') ?: '-' }}</span>
                            @if ($template->is_default)<span class="active">Default</span>@endif
                        </div>
                        <div class="wa-template-actions">
                            <a href="{{ route('admin.marketing.whatsapp-templates.show', $template) }}" class="wa-mini-btn">View Detail</a>
                            <a href="{{ route('admin.marketing.whatsapp-templates.edit', $template) }}" class="wa-mini-btn">Edit</a>
                            <form method="POST" action="{{ route('admin.marketing.whatsapp-templates.default', $template) }}">@csrf<button class="wa-mini-btn" type="submit">Set Default</button></form>
                            <button class="wa-mini-btn js-send-test-template" data-url="{{ route('admin.marketing.whatsapp-templates.send-test', $template) }}" @disabled($template->status !== 'APPROVED')>Send Test</button>
                            <form method="POST" action="{{ route('admin.marketing.whatsapp-templates.destroy', $template) }}" onsubmit="return confirm('Hapus template ini?');">@csrf @method('DELETE')<button class="wa-mini-btn danger" type="submit">Delete</button></form>
                        </div>
                    </article>
                @endforeach
            </div>
            {{ $templates->links() }}
        @endif
        <pre id="whatsapp-template-test-result" class="wa-hub-alert" style="display:none;white-space:pre-wrap;"></pre>
    </section>

    <script>
        document.querySelectorAll('.js-send-test-template').forEach((button) => {
            button.addEventListener('click', async () => {
                const phone = window.prompt('Nomor tujuan test', '6281234567890');
                const resultBox = document.getElementById('whatsapp-template-test-result');
                if (!phone || !resultBox) return;
                resultBox.style.display = 'block';
                resultBox.textContent = 'Sending...';
                const response = await fetch(button.dataset.url, {
                    method: 'POST',
                    headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: JSON.stringify({phone}),
                });
                resultBox.classList.toggle('success', response.ok);
                resultBox.textContent = JSON.stringify(await response.json(), null, 2);
            });
        });
    </script>

    <style>
        .wa-template-hub{display:grid;gap:1rem}.wa-hub-header,.wa-template-card,.wa-empty-panel{border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 8px 24px rgba(47,43,61,.07)}.wa-hub-header{display:flex;justify-content:space-between;gap:1rem;padding:1.1rem}.wa-hub-header span{color:#6f6b7d;font-weight:800;text-transform:uppercase;font-size:.76rem}.wa-hub-header h1{margin:.15rem 0;color:#2f2b3d}.wa-hub-header p{margin:0;color:#6f6b7d}.wa-hub-actions,.wa-template-actions{display:flex;gap:.55rem;flex-wrap:wrap;align-items:center}.wa-hub-actions form,.wa-template-actions form{margin:0}.wa-hub-btn,.wa-mini-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid rgba(47,43,61,.12);font-weight:800;text-decoration:none;cursor:pointer}.wa-hub-btn{min-height:2.4rem;padding:.5rem .85rem}.wa-mini-btn{min-height:2rem;padding:.35rem .58rem;background:#f6f6f8;color:#4b465c;font-size:.78rem}.wa-hub-btn.primary{background:#28c76f;color:#fff;border-color:#28c76f}.wa-hub-btn.secondary{background:#fff;color:#2f2b3d}.wa-mini-btn.danger{color:#c23a3b}.wa-hub-alert{padding:.85rem 1rem;border-radius:8px;background:#fff;border:1px solid rgba(47,43,61,.1)}.wa-hub-alert.success{background:#f0fbf5;color:#168a49}.wa-hub-alert.error{background:#fff5f5;color:#b42324}.wa-empty-panel{display:grid;gap:.4rem;padding:2rem;text-align:center;color:#6f6b7d}.wa-empty-panel strong{color:#2f2b3d}.wa-template-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}.wa-template-card{display:grid;gap:.85rem;padding:1rem}.wa-template-card-top{display:flex;justify-content:space-between;gap:.75rem}.wa-template-card h2{margin:0;color:#2f2b3d;font-size:1.05rem}.wa-template-card p{margin:.25rem 0 0;color:#6f6b7d}.wa-template-meta{display:flex;gap:.45rem;flex-wrap:wrap}.wa-template-meta span,.wa-status{border-radius:999px;padding:.22rem .55rem;font-size:.76rem;font-weight:800;background:#f1f1f2;color:#4b465c}.wa-template-meta .active,.wa-status-approved{background:#e8f8ef;color:#168a49}.wa-status-pending{background:#fff6e8;color:#a35a00}.wa-status-rejected{background:#fff0f0;color:#c23a3b}@media(max-width:720px){.wa-hub-header{display:grid}.wa-hub-actions,.wa-hub-actions form,.wa-hub-btn{width:100%}}
    </style>
@endsection
