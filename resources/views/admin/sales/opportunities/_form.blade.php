@php
    $opportunity = $opportunity ?? null;
@endphp

<div class="sales-form-sections">
    <section class="sales-form-section">
        <div class="sales-form-section-head">
            <h3 data-lang-en="Relationship Context" data-lang-id="Konteks Relasi">Relationship Context</h3>
            <p data-lang-en="Link the opportunity to an existing lead or customer before the pipeline starts moving." data-lang-id="Hubungkan opportunity ke lead atau customer yang sudah ada sebelum pipeline bergerak.">Link the opportunity to an existing lead or customer before the pipeline starts moving.</p>
        </div>

        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Lead" data-lang-id="Lead">Lead</span>
                <select name="lead_id">
                    <option value="" data-lang-en="No lead" data-lang-id="Tanpa lead">Tanpa lead</option>
                    @foreach ($leads as $lead)
                        <option value="{{ $lead->id }}" @selected((string) old('lead_id', $opportunity->lead_id ?? '') === (string) $lead->id)>{{ $lead->name }}</option>
                    @endforeach
                </select>
                @error('lead_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Customer" data-lang-id="Customer">Customer</span>
                <select name="customer_id">
                    <option value="" data-lang-en="No customer" data-lang-id="Tanpa customer">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $opportunity->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span data-lang-en="Title" data-lang-id="Judul">Title</span> <strong>*</strong>
                <input type="text" name="title" value="{{ old('title', $opportunity->title ?? '') }}" maxlength="255" required>
                @error('title')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Company Name" data-lang-id="Nama Perusahaan">Company Name</span>
                <input type="text" name="company_name" value="{{ old('company_name', $opportunity->company_name ?? '') }}" maxlength="255">
                @error('company_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Contact Name" data-lang-id="Nama Kontak">Contact Name</span>
                <input type="text" name="contact_name" value="{{ old('contact_name', $opportunity->contact_name ?? '') }}" maxlength="255">
                @error('contact_name')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="sales-form-section">
        <div class="sales-form-section-head">
            <h3 data-lang-en="Commercial Snapshot" data-lang-id="Snapshot Komersial">Commercial Snapshot</h3>
            <p data-lang-en="Define the size, likelihood, and current stage of this opportunity." data-lang-id="Tentukan nilai, peluang closing, dan tahap pipeline saat ini.">Define the size, likelihood, and current stage of this opportunity.</p>
        </div>

        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Estimated Value" data-lang-id="Estimasi Nilai">Estimated Value</span>
                <input type="number" name="estimated_value" value="{{ old('estimated_value', $opportunity->estimated_value ?? 0) }}" min="0" step="0.01">
                @error('estimated_value')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Probability" data-lang-id="Probabilitas">Probability</span>
                <input type="number" name="probability" value="{{ old('probability', $opportunity->probability ?? 0) }}" min="0" max="100" step="1">
                @error('probability')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Status" data-lang-id="Status">Status</span> <strong>*</strong>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected(old('status', $opportunity->status ?? 'open') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Expected Close Date" data-lang-id="Tanggal Estimasi Closing">Expected Close Date</span>
                <input type="date" name="expected_close_date" value="{{ old('expected_close_date', optional($opportunity->expected_close_date ?? null)->format('Y-m-d')) }}">
                @error('expected_close_date')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</span>
                <input type="text" name="assigned_to" value="{{ old('assigned_to', $opportunity->assigned_to ?? '') }}" maxlength="255">
                @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="sales-form-section">
        <div class="sales-form-section-head">
            <h3 data-lang-en="Discovery Notes" data-lang-id="Catatan Discovery">Discovery Notes</h3>
            <p data-lang-en="Record objections, buying signals, and context that the team should keep visible." data-lang-id="Catat objection, buying signal, dan konteks penting yang perlu terlihat oleh tim.">Record objections, buying signals, and context that the team should keep visible.</p>
        </div>

        <div class="customer-form-grid">
            <label class="field field-full">
                <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
                <textarea name="notes" rows="5">{{ old('notes', $opportunity->notes ?? '') }}</textarea>
                @error('notes')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>
</div>
