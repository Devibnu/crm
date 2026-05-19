@php
    $quotation = $quotation ?? null;
    $selectedStatus = old('status', $quotation->status ?? 'draft');
    $selectedOpportunityId = old('opportunity_id', $quotation->opportunity_id ?? $prefillOpportunityId ?? '');
    $selectedCustomerId = old('customer_id', $quotation->customer_id ?? $prefillCustomerId ?? '');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Deal Context" data-lang-id="Konteks Deal">Deal Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Opportunity" data-lang-id="Opportunity">Opportunity</span>
                <select name="opportunity_id">
                    <option value="" data-lang-en="No opportunity" data-lang-id="Tanpa opportunity">Tanpa opportunity</option>
                    @foreach ($opportunities as $opportunity)
                        <option value="{{ $opportunity->id }}" @selected((string) $selectedOpportunityId === (string) $opportunity->id)>{{ $opportunity->title }}</option>
                    @endforeach
                </select>
                @error('opportunity_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Customer" data-lang-id="Customer">Customer</span>
                <select name="customer_id">
                    <option value="" data-lang-en="No customer" data-lang-id="Tanpa customer">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Quotation Information" data-lang-id="Informasi Quotation">Quotation Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Quote Number" data-lang-id="Nomor Quotation">Quote Number</span> <strong>*</strong>
                <input type="text" name="quote_number" value="{{ old('quote_number', $quotation->quote_number ?? '') }}" maxlength="255" placeholder="QTN-2026-0001" data-placeholder-en="QTN-2026-0001" data-placeholder-id="QTN-2026-0001" required>
                @error('quote_number')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Title" data-lang-id="Judul">Title</span> <strong>*</strong>
                <input type="text" name="title" value="{{ old('title', $quotation->title ?? '') }}" maxlength="255" placeholder="Enterprise CRM proposal" data-placeholder-en="Enterprise CRM proposal" data-placeholder-id="Enterprise CRM proposal" required>
                @error('title')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Amount" data-lang-id="Nominal">Amount</span> <strong>*</strong>
                <input type="number" name="amount" value="{{ old('amount', $quotation->amount ?? 0) }}" min="0" step="0.01" required>
                @error('amount')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Status" data-lang-id="Status">Status</span> <strong>*</strong>
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
        <h2 data-lang-en="Timeline & Notes" data-lang-id="Timeline & Catatan">Timeline & Notes</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Issued At" data-lang-id="Diterbitkan Pada">Issued At</span>
                <input type="date" name="issued_at" value="{{ old('issued_at', optional($quotation->issued_at ?? null)->format('Y-m-d')) }}">
                @error('issued_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Valid Until" data-lang-id="Berlaku Sampai">Valid Until</span>
                <input type="date" name="valid_until" value="{{ old('valid_until', optional($quotation->valid_until ?? null)->format('Y-m-d')) }}">
                @error('valid_until')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
            <textarea name="notes" rows="5" placeholder="Catatan negosiasi, syarat khusus, atau follow-up berikutnya." data-placeholder-en="Negotiation notes, special terms, or next follow-up." data-placeholder-id="Catatan negosiasi, syarat khusus, atau follow-up berikutnya.">{{ old('notes', $quotation->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
