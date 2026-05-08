@php
    $quotation = $quotation ?? null;
    $selectedStatus = old('status', $quotation->status ?? 'draft');
    $selectedOpportunityId = old('opportunity_id', $quotation->opportunity_id ?? $prefillOpportunityId ?? '');
    $selectedCustomerId = old('customer_id', $quotation->customer_id ?? $prefillCustomerId ?? '');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Deal Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Opportunity</span>
                <select name="opportunity_id">
                    <option value="">Tanpa opportunity</option>
                    @foreach ($opportunities as $opportunity)
                        <option value="{{ $opportunity->id }}" @selected((string) $selectedOpportunityId === (string) $opportunity->id)>{{ $opportunity->title }}</option>
                    @endforeach
                </select>
                @error('opportunity_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Customer</span>
                <select name="customer_id">
                    <option value="">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Quotation Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Quote Number <strong>*</strong></span>
                <input type="text" name="quote_number" value="{{ old('quote_number', $quotation->quote_number ?? '') }}" maxlength="255" placeholder="QTN-2026-0001" required>
                @error('quote_number')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Title <strong>*</strong></span>
                <input type="text" name="title" value="{{ old('title', $quotation->title ?? '') }}" maxlength="255" placeholder="Enterprise CRM proposal" required>
                @error('title')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Amount <strong>*</strong></span>
                <input type="number" name="amount" value="{{ old('amount', $quotation->amount ?? 0) }}" min="0" step="0.01" required>
                @error('amount')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Status <strong>*</strong></span>
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
        <h2>Timeline & Notes</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Issued At</span>
                <input type="date" name="issued_at" value="{{ old('issued_at', optional($quotation->issued_at ?? null)->format('Y-m-d')) }}">
                @error('issued_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Valid Until</span>
                <input type="date" name="valid_until" value="{{ old('valid_until', optional($quotation->valid_until ?? null)->format('Y-m-d')) }}">
                @error('valid_until')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span>Notes</span>
            <textarea name="notes" rows="5" placeholder="Catatan negosiasi, syarat khusus, atau follow-up berikutnya.">{{ old('notes', $quotation->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
