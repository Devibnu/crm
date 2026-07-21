@php
    $transaction = $transaction ?? null;
    $isCustomerScopedCreate = isset($selectedCustomer) && ! $transaction;
    $selectedCustomerId = old('customer_id', $transaction->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedStatus = old('status', $transaction->status ?? 'pending');
@endphp

<div class="lead-form-groups customer-form-groups">
    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Customer Context</h2>
            <p>{{ $isCustomerScopedCreate ? 'Locked customer for this transaction record.' : 'Customer connected to this transaction record.' }}</p>
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
            <h2>Transaction Details</h2>
            <p>Deal name, current status, and expected closing date.</p>
        </div>

        <div class="lead-form-grid customer-transaction-detail-grid">
            <label class="field field-full">
                <span>Title <strong>*</strong></span>
                <input type="text" name="title" value="{{ old('title', $transaction->title ?? '') }}" maxlength="255" required>
                @error('title')<small class="error">{{ $message }}</small>@enderror
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

            <label class="field">
                <span>Closing Date</span>
                <input type="date" name="closing_date" value="{{ old('closing_date', optional($transaction->closing_date ?? null)->format('Y-m-d')) }}">
                @error('closing_date')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Financial Details</h2>
            <p>Transaction value recorded for this customer.</p>
        </div>

        <div class="lead-form-grid customer-transaction-financial-grid">
            <label class="field">
                <span>Amount <strong>*</strong></span>
                <input type="number" name="amount" value="{{ old('amount', $transaction->amount ?? '') }}" min="0" step="0.01" required>
                @error('amount')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Notes</h2>
            <p>Additional context for this transaction.</p>
        </div>

        <label class="field">
            <span>Description</span>
            <textarea name="description" rows="3">{{ old('description', $transaction->description ?? '') }}</textarea>
            @error('description')<small class="error">{{ $message }}</small>@enderror
        </label>
    </section>
</div>
