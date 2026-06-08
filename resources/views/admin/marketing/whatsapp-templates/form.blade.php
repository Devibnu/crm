@extends('admin.layouts.app')

@section('title', ($template ? 'Edit' : 'Tambah').' WhatsApp Template - Krakatau CRM')

@section('content')
    @php
        $defaultPresetKey = array_key_first($presets);
        $defaultPreset = (! $template && ! old('name')) ? ($presets[$defaultPresetKey] ?? null) : null;
        $selectedPreset = old('preset', $defaultPreset ? $defaultPresetKey : '');
        $initialName = old('name', $template->name ?? $defaultPresetKey ?? '');
        $initialCategory = old('category', $template->category ?? $defaultPreset['category'] ?? 'UTILITY');
        $initialBody = old('body', $template->body ?? $defaultPreset['body'] ?? '');
        $categoryLabels = [
            'UTILITY' => 'Direkomendasikan / Approval lebih aman',
            'MARKETING' => 'Perlu review lebih ketat',
            'AUTHENTICATION' => 'Khusus OTP/kode',
        ];
    @endphp
    <section class="wa-template-form-page">
        <div class="wa-form-card">
            <div class="wa-form-head">
                <div>
                    <span>WhatsApp Templates</span>
                    <h1>{{ $template ? 'Edit Template' : 'Tambah Template' }}</h1>
                    <p>Template akan dikirim ke Meta untuk ditinjau. Biasanya membutuhkan beberapa menit hingga beberapa jam.</p>
                </div>
                <a href="{{ route('admin.marketing.whatsapp-templates.index') }}" class="wa-form-btn secondary">Back</a>
            </div>

            @if (session('error'))<div class="wa-form-alert">{{ session('error') }}</div>@endif
            @if (! $provider)<div class="wa-form-alert">Hubungkan WhatsApp Business Cloud API terlebih dahulu.</div>@endif

            <form method="POST" action="{{ $template ? route('admin.marketing.whatsapp-templates.update', $template) : route('admin.marketing.whatsapp-templates.store') }}" class="wa-form-grid">
                @csrf
                @if ($template) @method('PUT') @endif
                <div class="wa-form-fields">
                    <label class="full"><span>Pilih template siap pakai</span><select id="preset-select" name="preset"><option value="">Custom</option>@foreach(['UTILITY','MARKETING','AUTHENTICATION'] as $group)<optgroup label="{{ $group }} - {{ $categoryLabels[$group] }}">@foreach($presets as $key => $preset)@if($preset['category'] === $group)<option value="{{ $key }}" data-category="{{ $preset['category'] }}" data-label="{{ $preset['label'] }}" data-body="{{ $preset['body'] }}" @selected($selectedPreset === $key)>{{ $key }}</option>@endif @endforeach</optgroup>@endforeach</select><small id="preset-label">{{ $defaultPreset['label'] ?? 'Pilih preset atau tulis template custom.' }}</small></label>
                    <div class="wa-safe-note full">Template ini mengikuti panduan aman agar peluang approval Meta lebih tinggi.</div>
                    <label><span>Nama Template</span><input id="template-name" name="name" value="{{ $initialName }}" required></label>
                    <label><span>Safe Name otomatis untuk Meta</span><input id="safe-name" value="{{ old('safe_name', $template->safe_name ?? $initialName) }}" readonly></label>
                    <label><span>Kategori</span><select id="category" name="category" required>@foreach(['UTILITY','MARKETING','AUTHENTICATION'] as $category)<option value="{{ $category }}" @selected($initialCategory === $category)>{{ $category }} - {{ $categoryLabels[$category] }}</option>@endforeach</select></label>
                    <label><span>Bahasa</span><select name="language">@foreach(['id','en_US'] as $lang)<option value="{{ $lang }}" @selected(old('language', $template->language ?? 'id') === $lang)>{{ $lang }}</option>@endforeach</select></label>
                    <label><span>Header optional</span><input id="header" name="header" value="{{ old('header', $template->header ?? '') }}"></label>
                    <label class="full"><span>Body wajib</span><textarea id="body" name="body" rows="7" required>{{ $initialBody }}</textarea></label>
                    <label><span>Footer optional</span><input id="footer" name="footer" value="{{ old('footer', $template->footer ?? '') }}"></label>
                    <div class="wa-helper full"><strong>Variable helper:</strong> @foreach(array_keys($examples) as $var)<button type="button" data-var="{{ $var }}">{{ '{' . '{' . $var . '}' . '}' }}</button>@endforeach</div>
                    @error('body')<div class="wa-form-alert full">{{ $message }}</div>@enderror
                    <button id="template-submit" class="wa-form-btn primary full" type="submit" data-provider-missing="{{ $provider ? '0' : '1' }}" @disabled(! $provider)>Submit to Meta</button>
                </div>
                <aside class="wa-preview-panel">
                    <div class="wa-score" id="readiness-score">High Approval Chance</div>
                    <ul id="readiness-reasons"></ul>
                    <div class="wa-preview-phone"><div id="preview-header"></div><div id="preview-body">-</div><small id="preview-footer"></small></div>
                    <div class="wa-examples"><strong>Example values</strong>@foreach($examples as $key => $value)<span>{{ $key }} = {{ $value }}</span>@endforeach</div>
                </aside>
            </form>
        </div>
    </section>
    <script>
        const examples = @json($examples);
        const categoryLabels = @json($categoryLabels);
        const promoWords = ['promo','diskon','gratis','sale','murah','voucher','cashback','beli sekarang','penawaran','limited','flash sale','bonus','hadiah'];
        const nameInput = document.getElementById('template-name'), safeInput = document.getElementById('safe-name'), category = document.getElementById('category'), body = document.getElementById('body'), header = document.getElementById('header'), footer = document.getElementById('footer'), submitButton = document.getElementById('template-submit'), presetLabel = document.getElementById('preset-label');
        const snake = value => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'').replace(/_+/g,'_');
        const update = () => {
            safeInput.value = snake(nameInput.value);
            document.getElementById('preview-header').textContent = header.value || '';
            document.getElementById('preview-body').textContent = body.value || '-';
            document.getElementById('preview-footer').textContent = footer.value || '';
            const reasons = []; const lower = body.value.toLowerCase();
            if (category.value === 'UTILITY' && promoWords.some(word => lower.includes(word))) reasons.push('Template Utility tidak boleh berisi promosi. Gunakan kategori Marketing atau ubah kalimat agar informatif.');
            if (category.value === 'AUTHENTICATION' && !/(kode|otp|verifikasi|autentikasi)/i.test(body.value)) reasons.push('Template Authentication hanya untuk OTP/kode/verifikasi.');
            if (/https?:\/\/|www\./i.test(body.value)) reasons.push('Template baru tidak boleh berisi link.');
            const variables = [...body.value.matchAll(/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/g)].map(match => match[1]);
            variables.forEach(variable => { if (!examples[variable]) reasons.push(`Variable ${variable} belum punya contoh otomatis.`); });
            const emojiCount = [...body.value.matchAll(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu)].length;
            if (emojiCount > 2) reasons.push('Emoji maksimal 2 agar template tetap aman.');
            if (body.value.length > 800) reasons.push('Body terlalu panjang. Idealnya 1-3 paragraf pendek.');
            const level = reasons.length === 0 ? 'High Approval Chance' : (reasons.length <= 1 ? 'Medium' : 'Risky');
            document.getElementById('readiness-score').textContent = level;
            document.getElementById('readiness-score').className = `wa-score ${level.toLowerCase().replaceAll(' ', '-')}`;
            document.getElementById('readiness-reasons').innerHTML = reasons.map(reason => `<li>${reason}</li>`).join('');
            if (submitButton) submitButton.disabled = submitButton.dataset.providerMissing === '1' || level === 'Risky';
        };
        [nameInput, category, body, header, footer].forEach(el => el?.addEventListener('input', update)); update();
        document.querySelectorAll('[data-var]').forEach(btn => btn.addEventListener('click', () => { body.value += ` ${'{' + '{'}${btn.dataset.var}${'}' + '}'}`; update(); }));
        document.getElementById('preset-select').addEventListener('change', event => { const option = event.target.selectedOptions[0]; if (!option?.value) { presetLabel.textContent = 'Custom template. Gunakan kategori dan body yang mengikuti panduan Meta.'; update(); return; } nameInput.value = option.value; category.value = option.dataset.category; body.value = option.dataset.body; presetLabel.textContent = option.dataset.label || categoryLabels[option.dataset.category] || ''; update(); });
    </script>
    <style>
        .wa-template-form-page{display:grid;gap:1rem}.wa-form-card{background:#fff;border:1px solid rgba(47,43,61,.08);border-radius:8px;box-shadow:0 8px 24px rgba(47,43,61,.07);padding:1rem}.wa-form-head{display:flex;justify-content:space-between;gap:1rem;margin-bottom:1rem}.wa-form-head span{color:#6f6b7d;font-weight:800;text-transform:uppercase;font-size:.76rem}.wa-form-head h1{margin:.15rem 0;color:#2f2b3d}.wa-form-head p{margin:0;color:#6f6b7d}.wa-form-grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:1rem}.wa-form-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.wa-form-fields label{display:grid;gap:.3rem;color:#6f6b7d;font-weight:700}.wa-form-fields input,.wa-form-fields select,.wa-form-fields textarea{border:1px solid rgba(47,43,61,.15);border-radius:8px;padding:.55rem;color:#2f2b3d}.wa-form-fields small{color:#168a49;font-weight:800}.wa-safe-note{padding:.72rem .85rem;border-radius:8px;background:#f0fbf5;color:#168a49;border:1px solid rgba(40,199,111,.18);font-weight:800}.full{grid-column:1/-1}.wa-form-btn{border-radius:8px;border:1px solid rgba(47,43,61,.12);padding:.55rem .85rem;font-weight:800;text-decoration:none;cursor:pointer}.wa-form-btn.primary{background:#28c76f;color:#fff;border-color:#28c76f}.wa-form-btn.secondary{background:#fff;color:#2f2b3d}.wa-form-btn:disabled{opacity:.55;cursor:not-allowed}.wa-form-alert{padding:.75rem;border-radius:8px;background:#fff5f5;color:#b42324}.wa-helper{display:flex;gap:.4rem;flex-wrap:wrap}.wa-helper button{border:0;border-radius:999px;background:#e8f8ef;color:#168a49;padding:.25rem .5rem;font-weight:800}.wa-preview-panel{display:grid;gap:.75rem;align-content:start;background:#f8f7fb;border-radius:8px;padding:1rem}.wa-score{width:max-content;border-radius:999px;background:#e8f8ef;color:#168a49;padding:.28rem .6rem;font-weight:800}.wa-score.medium{background:#fff4e5;color:#a35a00}.wa-score.risky{background:#fff0f0;color:#c23a3b}.wa-preview-phone{background:#efe7dc;border-radius:8px;padding:1rem}.wa-preview-phone>div{background:#fff;border-radius:8px;padding:.65rem;margin-bottom:.35rem;white-space:pre-wrap}.wa-preview-phone small{display:block;color:#8b8794}.wa-examples{display:grid;gap:.2rem;color:#6f6b7d}.wa-examples span{font-size:.82rem}@media(max-width:900px){.wa-form-grid,.wa-form-fields{grid-template-columns:1fr}.wa-form-head{display:grid}}
    </style>
@endsection
