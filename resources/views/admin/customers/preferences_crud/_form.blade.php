@php
    $preference = $preference ?? null;
    $selectedCustomerId = old('customer_id', $preference->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedChannel = old('preferred_channel', $preference->preferred_channel ?? 'none');
@endphp

<div class="customer-form-grid">
    <label class="field">
        <span>Customer <strong>*</strong></span>
        <select name="customer_id" required>
            <option value="">Pilih customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Preferred Channel <strong>*</strong></span>
        <select name="preferred_channel" required>
            @foreach ($preferredChannelOptions as $channel)
                <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
            @endforeach
        </select>
        @error('preferred_channel')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Product Interest</span>
        <input type="text" name="product_interest" value="{{ old('product_interest', $preference->product_interest ?? '') }}" maxlength="255">
        @error('product_interest')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Segment</span>
        <input type="text" name="segment" value="{{ old('segment', $preference->segment ?? '') }}" maxlength="255">
        @error('segment')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full preference-consent-field">
        <span>Communication Consent</span>
        <label class="preference-checkbox">
            <input type="checkbox" name="communication_consent" value="1" @checked(old('communication_consent', $preference->communication_consent ?? false))>
            <span>Customer memberikan consent komunikasi</span>
        </label>
        @error('communication_consent')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Notes</span>
        <textarea name="notes" rows="4">{{ old('notes', $preference->notes ?? '') }}</textarea>
        @error('notes')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
