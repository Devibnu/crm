@php
    $behavior = $behavior ?? null;
    $selectedCustomerId = old('customer_id', $behavior->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedLifecycleStage = old('lifecycle_stage', $behavior->lifecycle_stage ?? 'lead');
    $lastActivityAt = old('last_activity_at', optional($behavior->last_activity_at ?? null)->format('Y-m-d\TH:i'));
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
        <span data-lang-en="Lifecycle Stage" data-lang-id="Lifecycle Stage">Lifecycle Stage</span> <strong>*</strong>
        <select name="lifecycle_stage" required>
            @foreach ($lifecycleStageOptions as $stage)
                <option value="{{ $stage }}" @selected($selectedLifecycleStage === $stage)>{{ ucfirst($stage) }}</option>
            @endforeach
        </select>
        @error('lifecycle_stage')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Engagement Score" data-lang-id="Skor Engagement">Engagement Score</span> <strong>*</strong>
        <input type="number" name="engagement_score" value="{{ old('engagement_score', $behavior->engagement_score ?? 0) }}" min="0" max="100" required>
        @error('engagement_score')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Last Activity" data-lang-id="Aktivitas Terakhir">Last Activity</span>
        <input type="datetime-local" name="last_activity_at" value="{{ $lastActivityAt }}">
        @error('last_activity_at')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Product Interest" data-lang-id="Minat Produk">Product Interest</span>
        <input type="text" name="product_interest" value="{{ old('product_interest', $behavior->product_interest ?? '') }}" maxlength="255">
        @error('product_interest')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Behavior Notes" data-lang-id="Catatan Perilaku">Behavior Notes</span>
        <textarea name="behavior_notes" rows="4">{{ old('behavior_notes', $behavior->behavior_notes ?? '') }}</textarea>
        @error('behavior_notes')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
