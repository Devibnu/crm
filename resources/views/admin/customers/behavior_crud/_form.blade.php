@php
    $behavior = $behavior ?? null;
    $isCustomerScopedCreate = isset($selectedCustomer) && ! $behavior;
    $selectedCustomerId = old('customer_id', $behavior->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedLifecycleStage = old('lifecycle_stage', $behavior->lifecycle_stage ?? 'lead');
    $lastActivityAt = old('last_activity_at', optional($behavior->last_activity_at ?? null)->format('Y-m-d\TH:i'));
@endphp

<div class="lead-form-groups customer-form-groups">
    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Customer Context</h2>
            <p>{{ $isCustomerScopedCreate ? 'Locked customer for this behavior record.' : 'Customer connected to this behavior record.' }}</p>
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
            <h2>Lifecycle Information</h2>
            <p>Current lifecycle stage for this customer behavior profile.</p>
        </div>

        <div class="lead-form-grid customer-behavior-lifecycle-grid">
            <label class="field">
                <span>Lifecycle Stage <strong>*</strong></span>
                <select name="lifecycle_stage" required>
                    @foreach ($lifecycleStageOptions as $stage)
                        <option value="{{ $stage }}" @selected($selectedLifecycleStage === $stage)>{{ ucfirst($stage) }}</option>
                    @endforeach
                </select>
                @error('lifecycle_stage')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Engagement Information</h2>
            <p>Engagement score and latest known activity timing.</p>
        </div>

        <div class="lead-form-grid customer-behavior-engagement-grid">
            <label class="field">
                <span>Engagement Score <strong>*</strong></span>
                <input type="number" name="engagement_score" value="{{ old('engagement_score', $behavior->engagement_score ?? 0) }}" min="0" max="100" required>
                @error('engagement_score')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Last Activity</span>
                <input type="datetime-local" name="last_activity_at" value="{{ $lastActivityAt }}">
                @error('last_activity_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Product Interest</h2>
            <p>Product or service interest associated with this behavior record.</p>
        </div>

        <div class="lead-form-grid customer-behavior-product-grid">
            <label class="field">
                <span>Product Interest</span>
                <input type="text" name="product_interest" value="{{ old('product_interest', $behavior->product_interest ?? '') }}" maxlength="255">
                @error('product_interest')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Notes</h2>
            <p>Additional qualitative behavior notes.</p>
        </div>

        <label class="field">
            <span>Behavior Notes</span>
            <textarea name="behavior_notes" rows="3">{{ old('behavior_notes', $behavior->behavior_notes ?? '') }}</textarea>
            @error('behavior_notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </section>
</div>
