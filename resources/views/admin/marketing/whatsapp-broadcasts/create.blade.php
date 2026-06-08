@extends('admin.layouts.app')

@section('title', 'Buat Kampanye WhatsApp - Krakatau CRM')

@section('content')
    @php($selectedTemplateId = old('whatsapp_message_template_id', $approvedTemplates->first()?->id))
    <section class="wa-create-page">
        <header class="wa-create-header">
            <div>
                <span>WhatsApp / Kampanye / Create</span>
                <h1>Buat Kampanye WA Blast</h1>
                <p>Pilih template approved, target audience, jadwal pengiriman, lalu lihat estimasi biaya sebelum membuat campaign.</p>
            </div>
            <a href="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="wa-btn wa-btn-secondary">Batal</a>
        </header>

        @if ($approvedTemplates->isEmpty())
            <article class="wa-empty-template">
                <strong>Belum ada template approved. Buat atau sync template terlebih dahulu.</strong>
                <a href="{{ route('admin.marketing.whatsapp-templates.index') }}" class="wa-btn wa-btn-primary">Buka WhatsApp Templates</a>
            </article>
        @endif

        <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.store') }}" class="wa-campaign-form">
            @csrf
            <div class="wa-left-column">
                <article class="wa-card">
                    <h2>Detail Kampanye</h2>
                    <label><span>Nama Kampanye *</span><input name="name" value="{{ old('name') }}" placeholder="Promo Akhir Bulan" required></label>
                    @error('name')<small class="error">{{ $message }}</small>@enderror
                    <label><span>Deskripsi</span><textarea name="notes" rows="3" placeholder="Catatan internal campaign">{{ old('notes') }}</textarea></label>
                    <input type="hidden" name="send_mode" id="send_mode" value="meta_template">
                    <label>
                        <span>Template Pesan *</span>
                        <select name="whatsapp_message_template_id" id="template-select" @disabled($approvedTemplates->isEmpty()) required>
                            @foreach ($approvedTemplates as $template)
                                <option value="{{ $template->id }}" data-name="{{ $template->name }}" data-category="{{ $template->category }}" data-language="{{ $template->language }}" data-body="{{ $template->body ?: $template->body_meta }}" data-mapping='@json($template->variable_mapping ?? [])' @selected((string) $selectedTemplateId === (string) $template->id)>
                                    {{ $template->name }} - {{ $template->category }} / {{ $template->language }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @error('whatsapp_message_template_id')<small class="error">{{ $message }}</small>@enderror

                    <div class="wa-template-preview">
                        <span>Preview WhatsApp</span>
                        <div class="wa-bubble" id="template-preview">-</div>
                        <small id="template-mapping">Variable mapping: -</small>
                    </div>

                    <div class="wa-custom-warning">
                        Custom text hanya dapat digunakan dalam sesi 24 jam atau provider tertentu. Untuk WA Blast resmi, gunakan Meta Template approved.
                    </div>
                    <textarea name="message_template" id="message-template" hidden>{{ old('message_template') }}</textarea>
                </article>

                <article class="wa-card">
                    <h2>Target Penerima</h2>
                    <label>
                        <span>Target Audience *</span>
                        <select name="audience" id="audience-select" required>
                            @foreach ($audienceOptions as $key => $option)
                                <option value="{{ $key }}" data-count="{{ $audienceCounts[$key] ?? 0 }}" data-type="{{ $option['recipient_type'] }}" @disabled($option['disabled'] ?? false) @selected(old('audience', 'all_customers') === $key)>
                                    {{ $option['label'] }}{{ ($option['disabled'] ?? false) ? ' (belum tersedia)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <input type="hidden" name="target_type" id="target-type" value="customer">
                    <input type="hidden" name="recipient_type" id="recipient-type" value="customer">
                    <div class="wa-recipient-count">
                        <strong id="recipient-count">0</strong>
                        <span>penerima valid WhatsApp</span>
                    </div>
                    @error('target_type')<small class="error">{{ $message }}</small>@enderror
                </article>
            </div>

            <aside class="wa-right-column">
                <article class="wa-card">
                    <h2>Jadwal Pengiriman</h2>
                    <label><span>Mode</span><select name="schedule_type" id="schedule-type"><option value="draft">Simpan sebagai Draft</option><option value="now" selected>Kirim Sekarang</option><option value="scheduled">Jadwalkan</option></select></label>
                    <label id="scheduled-at-field" style="display:none;"><span>Waktu Jadwal</span><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"></label>
                </article>

                <article class="wa-card">
                    <h2>Pengaturan</h2>
                    <label><span>Rate limit pesan/detik</span><select name="rate_limit"><option>1</option><option>5</option><option selected>10</option><option>20</option></select></label>
                    <small>Rate limit lebih tinggi = pengiriman lebih cepat, tetapi berisiko throttling.</small>
                </article>

                <article class="wa-card wa-cost-card">
                    <h2>Estimasi Biaya</h2>
                    <div><span>Jumlah penerima</span><strong id="cost-recipients">0</strong></div>
                    <div><span>Harga per pesan</span><strong>Rp{{ number_format($pricePerMessage, 0, ',', '.') }}</strong></div>
                    <div class="total"><span>Total estimasi</span><strong id="cost-total">Rp0</strong></div>
                </article>

                <div class="wa-submit-actions">
                    <button type="submit" class="wa-btn wa-btn-primary" @disabled($approvedTemplates->isEmpty())>Buat Kampanye</button>
                    <button type="submit" class="wa-btn wa-btn-secondary" onclick="document.getElementById('schedule-type').value='draft'">Simpan Draft</button>
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="wa-btn wa-btn-ghost">Batal</a>
                </div>
            </aside>
        </form>
    </section>

    <script>
        const pricePerMessage = {{ (int) $pricePerMessage }};
        const templateSelect = document.getElementById('template-select');
        const preview = document.getElementById('template-preview');
        const mapping = document.getElementById('template-mapping');
        const messageTemplate = document.getElementById('message-template');
        const audience = document.getElementById('audience-select');
        const recipientCount = document.getElementById('recipient-count');
        const costRecipients = document.getElementById('cost-recipients');
        const costTotal = document.getElementById('cost-total');
        const targetType = document.getElementById('target-type');
        const recipientType = document.getElementById('recipient-type');
        const scheduleType = document.getElementById('schedule-type');
        const scheduledAtField = document.getElementById('scheduled-at-field');
        const rupiah = value => `Rp${Number(value).toLocaleString('id-ID')}`;
        const syncTemplate = () => {
            const option = templateSelect?.selectedOptions?.[0];
            const body = option?.dataset.body || '-';
            preview.textContent = body;
            messageTemplate.value = body;
            mapping.textContent = `Variable mapping: ${option?.dataset.mapping || '-'}`;
        };
        const syncAudience = () => {
            const option = audience?.selectedOptions?.[0];
            const count = Number(option?.dataset.count || 0);
            const type = option?.dataset.type || 'customer';
            recipientCount.textContent = count.toLocaleString('id-ID');
            costRecipients.textContent = count.toLocaleString('id-ID');
            costTotal.textContent = rupiah(count * pricePerMessage);
            targetType.value = type;
            recipientType.value = type;
        };
        const syncSchedule = () => scheduledAtField.style.display = scheduleType.value === 'scheduled' ? '' : 'none';
        templateSelect?.addEventListener('change', syncTemplate);
        audience?.addEventListener('change', syncAudience);
        scheduleType?.addEventListener('change', syncSchedule);
        syncTemplate(); syncAudience(); syncSchedule();
    </script>

    <style>
        .wa-create-page{display:grid;gap:1rem}.wa-create-header,.wa-card,.wa-empty-template{border:1px solid rgba(47,43,61,.08);border-radius:8px;background:#fff;box-shadow:0 8px 24px rgba(47,43,61,.07)}.wa-create-header{display:flex;justify-content:space-between;gap:1rem;padding:1.1rem;align-items:center}.wa-create-header span{color:#6f6b7d;font-weight:800;font-size:.78rem}.wa-create-header h1{margin:.15rem 0;color:#2f2b3d}.wa-create-header p{margin:0;color:#6f6b7d}.wa-campaign-form{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:1rem}.wa-left-column,.wa-right-column{display:grid;gap:1rem;align-content:start}.wa-card{display:grid;gap:.85rem;padding:1rem}.wa-card h2{margin:0;color:#2f2b3d;font-size:1.05rem}.wa-card label{display:grid;gap:.32rem;color:#6f6b7d;font-weight:800}.wa-card input,.wa-card select,.wa-card textarea{border:1px solid rgba(47,43,61,.15);border-radius:8px;padding:.6rem;color:#2f2b3d}.wa-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid rgba(47,43,61,.12);font-weight:800;text-decoration:none;cursor:pointer;min-height:2.35rem;padding:.5rem .85rem}.wa-btn-primary{background:#28c76f;color:#fff;border-color:#28c76f}.wa-btn-secondary{background:#fff;color:#4b465c}.wa-btn-ghost{background:#f6f6f8;color:#4b465c}.wa-empty-template{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem;color:#6f6b7d}.wa-template-preview{display:grid;gap:.4rem}.wa-template-preview>span{color:#6f6b7d;font-weight:900}.wa-bubble{max-width:92%;padding:.75rem .85rem;border-radius:8px 8px 8px 2px;background:#e8f8ef;color:#2f2b3d;white-space:pre-wrap}.wa-custom-warning{padding:.75rem;border-radius:8px;background:#fff6e8;color:#8a5200;font-weight:700}.wa-recipient-count{display:flex;align-items:flex-end;gap:.45rem;padding:.9rem;border-radius:8px;background:#f0fbf5;color:#168a49}.wa-recipient-count strong{font-size:1.8rem;line-height:1}.wa-cost-card div{display:flex;justify-content:space-between;gap:.75rem;color:#6f6b7d}.wa-cost-card strong{color:#2f2b3d}.wa-cost-card .total{padding-top:.75rem;border-top:1px solid rgba(47,43,61,.08)}.wa-cost-card .total strong{color:#168a49;font-size:1.25rem}.wa-submit-actions{display:grid;gap:.6rem}.error{color:#c23a3b}.wa-card small{color:#6f6b7d}@media(max-width:960px){.wa-campaign-form{grid-template-columns:1fr}.wa-create-header,.wa-empty-template{display:grid}.wa-btn{width:100%}}
    </style>
@endsection
