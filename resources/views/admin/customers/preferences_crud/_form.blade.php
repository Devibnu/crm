@php
    $preference = $preference ?? null;
    $isCustomerScopedCreate = isset($selectedCustomer) && ! $preference;
    $selectedCustomerId = old('customer_id', $preference->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedChannel = old('preferred_channel', $preference->preferred_channel ?? 'none');
@endphp

<div class="lead-form-groups customer-form-groups">
    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Customer Context</h2>
            <p>{{ $isCustomerScopedCreate ? 'Locked customer for this preference record.' : 'Customer connected to this preference record.' }}</p>
        </div>

        @if ($isCustomerScopedCreate)
            <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
            <div class="customer-context-panel">
                <span>@include('admin.partials.sidebar-icon', ['icon' => 'user'])</span>
                <div>
                    <strong>{{ $selectedCustomer->name }}</strong>
                    <small>{{ $selectedCustomer->company_name ?: 'No company' }}</small>
                    @if ($selectedCustomer->email || $selectedCustomer->phone)
                        <em>{{ $selectedCustomer->email ?: $selectedCustomer->phone }}</em>
                    @endif
                </div>
            </div>
            @error('customer_id')<small class="error">{{ $message }}</small>@enderror
        @else
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
        @endif
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Communication Preferences</h2>
            <p>Preferred channel and consent for customer communication.</p>
        </div>

        <div class="lead-form-grid customer-preference-communication-grid">
            <label class="field">
                <span>Preferred Channel <strong>*</strong></span>
                <select name="preferred_channel" required>
                    @foreach ($preferredChannelOptions as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
                    @endforeach
                </select>
                @error('preferred_channel')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field preference-consent-field">
                <span>Communication Consent</span>
                <label class="preference-checkbox">
                    <input type="checkbox" name="communication_consent" value="1" @checked(old('communication_consent', $preference->communication_consent ?? false))>
                    <span>Customer memberikan consent komunikasi</span>
                </label>
                @error('communication_consent')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Product / Segment Preferences</h2>
            <p>Interest and segment information used for relationship context.</p>
        </div>

        <div class="lead-form-grid customer-preference-segment-grid">
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
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Notes</h2>
            <p>Additional context for this preference record.</p>
        </div>

        <label class="field">
            <span>Notes</span>
            <textarea name="notes" rows="3">{{ old('notes', $preference->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </section>
</div>
