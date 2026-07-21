@php
    $interaction = $interaction ?? null;
    $isCustomerScopedCreate = isset($selectedCustomer) && ! $interaction;
    $selectedCustomerId = old('customer_id', $interaction->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedType = old('type', $interaction->type ?? 'call');
    $interactionAt = old('interaction_at', optional($interaction->interaction_at ?? null)->format('Y-m-d\TH:i'));
@endphp

<div class="lead-form-groups customer-form-groups">
    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Customer Context</h2>
            <p>{{ $isCustomerScopedCreate ? 'Locked customer for this interaction history.' : 'Customer connected to this interaction history.' }}</p>
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
            <h2>Interaction Details</h2>
            <p>Communication type, topic, and conversation notes.</p>
        </div>

        <div class="lead-form-grid customer-interaction-detail-grid">
            <label class="field">
                <span>Type <strong>*</strong></span>
                <select name="type" required>
                    @foreach ($typeOptions as $type)
                        <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
                @error('type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Subject <strong>*</strong></span>
                <input type="text" name="subject" value="{{ old('subject', $interaction->subject ?? '') }}" maxlength="255" required>
                @error('subject')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Description</span>
                <textarea name="description" rows="3">{{ old('description', $interaction->description ?? '') }}</textarea>
                @error('description')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Activity Information</h2>
            <p>Timing, owner text, and the resulting outcome.</p>
        </div>

        <div class="lead-form-grid customer-interaction-activity-grid">
            <label class="field">
                <span>Interaction Date</span>
                <input type="datetime-local" name="interaction_at" value="{{ $interactionAt }}">
                @error('interaction_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Handled By</span>
                <input type="text" name="handled_by" value="{{ old('handled_by', $interaction->handled_by ?? '') }}" maxlength="255">
                @error('handled_by')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Outcome</span>
                <input type="text" name="outcome" value="{{ old('outcome', $interaction->outcome ?? '') }}" maxlength="255">
                @error('outcome')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>
</div>
