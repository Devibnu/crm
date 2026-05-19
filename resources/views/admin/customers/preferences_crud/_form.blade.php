@php
    $preference = $preference ?? null;
    $selectedCustomerId = old('customer_id', $preference->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedChannel = old('preferred_channel', $preference->preferred_channel ?? 'none');
@endphp

<div class="customer-form-grid">
    <label class="field">
        <span data-lang-en="Customer" data-lang-id="Customer">Customer</span> <strong>*</strong>
        <select name="customer_id" required>
            <option value="" data-lang-en="Select customer" data-lang-id="Pilih customer">Pilih customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Preferred Channel" data-lang-id="Channel Pilihan">Preferred Channel</span> <strong>*</strong>
        <select name="preferred_channel" required>
            @foreach ($preferredChannelOptions as $channel)
                <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
            @endforeach
        </select>
        @error('preferred_channel')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Product Interest" data-lang-id="Minat Produk">Product Interest</span>
        <input type="text" name="product_interest" value="{{ old('product_interest', $preference->product_interest ?? '') }}" maxlength="255">
        @error('product_interest')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Segment" data-lang-id="Segmen">Segment</span>
        <input type="text" name="segment" value="{{ old('segment', $preference->segment ?? '') }}" maxlength="255">
        @error('segment')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full preference-consent-field">
        <span data-lang-en="Communication Consent" data-lang-id="Consent Komunikasi">Communication Consent</span>
        <label class="preference-checkbox">
            <input type="checkbox" name="communication_consent" value="1" @checked(old('communication_consent', $preference->communication_consent ?? false))>
            <span data-lang-en="Customer has granted communication consent" data-lang-id="Customer memberikan consent komunikasi">Customer has granted communication consent</span>
        </label>
        @error('communication_consent')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
        <textarea name="notes" rows="4">{{ old('notes', $preference->notes ?? '') }}</textarea>
        @error('notes')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
