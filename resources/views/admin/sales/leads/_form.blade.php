@php
    $lead = $lead ?? null;
@endphp

<div class="sales-form-sections">
    <section class="sales-form-section">
        <div class="sales-form-section-head">
            <h3 data-lang-en="Lead Identity" data-lang-id="Identitas Lead">Lead Identity</h3>
            <p data-lang-en="Capture who the prospect is and whether they already map to an existing customer record." data-lang-id="Catat siapa prospeknya dan apakah sudah terkait ke customer yang ada.">Capture who the prospect is and whether they already map to an existing customer record.</p>
        </div>

        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Customer" data-lang-id="Customer">Customer</span>
                <select name="customer_id">
                    <option value="" data-lang-en="No customer" data-lang-id="Tanpa customer">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $lead->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Lead Name" data-lang-id="Nama Lead">Lead Name</span> <strong>*</strong>
                <input type="text" name="name" value="{{ old('name', $lead->name ?? '') }}" maxlength="255" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Company Name" data-lang-id="Nama Perusahaan">Company Name</span>
                <input type="text" name="company_name" value="{{ old('company_name', $lead->company_name ?? '') }}" maxlength="255">
                @error('company_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Source" data-lang-id="Sumber">Source</span>
                <input type="text" name="source" value="{{ old('source', $lead->source ?? '') }}" maxlength="255">
                @error('source')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="sales-form-section">
        <div class="sales-form-section-head">
            <h3 data-lang-en="Contact Channel" data-lang-id="Channel Kontak">Contact Channel</h3>
            <p data-lang-en="Keep direct communication data clear so qualification follow-up can move fast." data-lang-id="Simpan data komunikasi langsung dengan rapi agar follow-up kualifikasi bisa bergerak cepat.">Keep direct communication data clear so qualification follow-up can move fast.</p>
        </div>

        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Email" data-lang-id="Email">Email</span>
                <input type="email" name="email" value="{{ old('email', $lead->email ?? '') }}" maxlength="255">
                @error('email')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Phone" data-lang-id="Telepon">Phone</span>
                <input type="text" name="phone" value="{{ old('phone', $lead->phone ?? '') }}" maxlength="100">
                @error('phone')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Status" data-lang-id="Status">Status</span> <strong>*</strong>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected(old('status', $lead->status ?? 'new') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Priority" data-lang-id="Prioritas">Priority</span> <strong>*</strong>
                <select name="priority" required>
                    @foreach ($priorityOptions as $priority)
                        <option value="{{ $priority }}" @selected(old('priority', $lead->priority ?? 'medium') === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                @error('priority')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</span>
                <input type="text" name="assigned_to" value="{{ old('assigned_to', $lead->assigned_to ?? '') }}" maxlength="255">
                @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="sales-form-section">
        <div class="sales-form-section-head">
            <h3 data-lang-en="Qualification Notes" data-lang-id="Catatan Kualifikasi">Qualification Notes</h3>
            <p data-lang-en="Record context, objections, and next-step cues that should stay visible to the team." data-lang-id="Catat konteks, objection, dan petunjuk next step yang perlu tetap terlihat oleh tim.">Record context, objections, and next-step cues that should stay visible to the team.</p>
        </div>

        <div class="customer-form-grid">
            <label class="field field-full">
                <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
                <textarea name="notes" rows="5">{{ old('notes', $lead->notes ?? '') }}</textarea>
                @error('notes')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>
</div>
