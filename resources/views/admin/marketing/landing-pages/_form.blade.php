@php
    $landingPage = $landingPage ?? null;
    $selectedStatus = old('status', $landingPage->status ?? 'draft');
    $formFieldsValue = old('form_fields', $formFieldsJson ?? '');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Landing Page Information" data-lang-id="Informasi Landing Page">Landing Page Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Campaign" data-lang-id="Campaign">Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="" data-lang-en="No campaign" data-lang-id="Tanpa campaign">Tanpa campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $landingPage->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Title" data-lang-id="Judul">Title</span> <strong>*</strong></span>
                <input type="text" name="title" value="{{ old('title', $landingPage->title ?? '') }}" maxlength="255" placeholder="Free CRM Consultation" data-placeholder-en="Free CRM Consultation" data-placeholder-id="Konsultasi CRM Gratis" required>
                @error('title')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Slug" data-lang-id="Slug">Slug</span> <strong>*</strong></span>
                <input type="text" name="slug" value="{{ old('slug', $landingPage->slug ?? '') }}" maxlength="255" placeholder="free-crm-consultation" data-placeholder-en="free-crm-consultation" data-placeholder-id="konsultasi-crm-gratis" required>
                @error('slug')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Status" data-lang-id="Status">Status</span> <strong>*</strong></span>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $landingPage->created_by ?? '') }}" maxlength="255" placeholder="Marketing Ops" data-placeholder-en="Marketing Ops" data-placeholder-id="Operasi Marketing">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Content" data-lang-id="Konten">Content</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Headline" data-lang-id="Headline">Headline</span>
                <input type="text" name="headline" value="{{ old('headline', $landingPage->headline ?? '') }}" maxlength="255" placeholder="Grow your pipeline with Krakatau CRM" data-placeholder-en="Grow your pipeline with Krakatau CRM" data-placeholder-id="Tingkatkan pipeline Anda dengan Krakatau CRM">
                @error('headline')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span data-lang-en="Subheadline" data-lang-id="Subheadline">Subheadline</span>
            <textarea name="subheadline" rows="4" placeholder="Short supporting copy for the landing page." data-placeholder-en="Short supporting copy for the landing page." data-placeholder-id="Copy pendukung singkat untuk landing page.">{{ old('subheadline', $landingPage->subheadline ?? '') }}</textarea>
            @error('subheadline')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field">
            <span data-lang-en="Thank You Message" data-lang-id="Pesan Terima Kasih">Thank You Message</span>
            <textarea name="thank_you_message" rows="4" placeholder="Thanks, our team will contact you soon." data-placeholder-en="Thanks, our team will contact you soon." data-placeholder-id="Terima kasih, tim kami akan segera menghubungi Anda.">{{ old('thank_you_message', $landingPage->thank_you_message ?? '') }}</textarea>
            @error('thank_you_message')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Form Builder" data-lang-id="Form Builder">Form Builder</h2>
        <label class="field">
            <span data-lang-en="Form Fields JSON" data-lang-id="JSON Field Formulir">Form Fields JSON</span>
            <textarea name="form_fields" rows="9" placeholder='[{"name":"full_name","type":"text","required":true},{"name":"email","type":"email","required":true}]' data-placeholder-en='[{"name":"full_name","type":"text","required":true},{"name":"email","type":"email","required":true}]' data-placeholder-id='[{"name":"nama_lengkap","type":"text","required":true},{"name":"email","type":"email","required":true}]'>{{ $formFieldsValue }}</textarea>
            @error('form_fields')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Publishing" data-lang-id="Publikasi">Publishing</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Published At" data-lang-id="Dipublikasikan Pada">Published At</span>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($landingPage->published_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('published_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Views" data-lang-id="Dilihat">Views</span>
                <input type="number" name="views_count" value="{{ old('views_count', $landingPage->views_count ?? 0) }}" min="0" step="1">
                @error('views_count')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Submissions" data-lang-id="Submission">Submissions</span>
                <input type="number" name="submissions_count" value="{{ old('submissions_count', $landingPage->submissions_count ?? 0) }}" min="0" step="1">
                @error('submissions_count')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>
