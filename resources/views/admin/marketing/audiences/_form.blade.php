@php
    $segment = $segment ?? null;
    $selectedType = old('type', $segment->type ?? 'demographic');
    $selectedStatus = old('status', $segment->status ?? 'active');
    $criteriaValue = old('criteria', $criteriaJson ?? '');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Segment Information" data-lang-id="Informasi Segmen">Segment Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span><span data-lang-en="Name" data-lang-id="Nama">Name</span> <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $segment->name ?? '') }}" maxlength="255" placeholder="High Intent Enterprise Audience" data-placeholder-en="High Intent Enterprise Audience" data-placeholder-id="Audiens Enterprise dengan Minat Tinggi" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Type" data-lang-id="Tipe">Type</span> <strong>*</strong></span>
                <select name="type" required>
                    @foreach ($typeOptions as $type)
                        <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
                @error('type')<small class="error">{{ $message }}</small>@enderror
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
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Audience Criteria" data-lang-id="Kriteria Audiens">Audience Criteria</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Estimated Audience" data-lang-id="Estimasi Audiens">Estimated Audience</span>
                <input type="number" name="estimated_audience" value="{{ old('estimated_audience', $segment->estimated_audience ?? 0) }}" min="0" step="1">
                @error('estimated_audience')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span data-lang-en="Criteria JSON" data-lang-id="JSON Kriteria">Criteria JSON</span>
            <textarea name="criteria" rows="8" placeholder='{"industry":["manufacturing"],"engagement_score":{">=":70}}' data-placeholder-en='{"industry":["manufacturing"],"engagement_score":{">=":70}}' data-placeholder-id='{"industri":["manufaktur"],"skor_keterlibatan":{">=":70}}'>{{ $criteriaValue }}</textarea>
            @error('criteria')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Description" data-lang-id="Deskripsi">Description</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $segment->created_by ?? '') }}" maxlength="255" placeholder="Marketing Ops" data-placeholder-en="Marketing Ops" data-placeholder-id="Operasi Marketing">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span data-lang-en="Description" data-lang-id="Deskripsi">Description</span>
            <textarea name="description" rows="5" placeholder="Segment purpose, targeting notes, and campaign usage." data-placeholder-en="Segment purpose, targeting notes, and campaign usage." data-placeholder-id="Tujuan segmen, catatan penargetan, dan penggunaan campaign.">{{ old('description', $segment->description ?? '') }}</textarea>
            @error('description')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
