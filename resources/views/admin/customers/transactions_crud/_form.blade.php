@php
    $transaction = $transaction ?? null;
    $selectedCustomerId = old('customer_id', $transaction->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedStatus = old('status', $transaction->status ?? 'pending');
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
        <span>Status <strong>*</strong></span>
        <select name="status" required>
            @foreach ($statusOptions as $status)
                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Title <strong>*</strong></span>
        <input type="text" name="title" value="{{ old('title', $transaction->title ?? '') }}" maxlength="255" required>
        @error('title')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Amount <strong>*</strong></span>
        <input type="number" name="amount" value="{{ old('amount', $transaction->amount ?? '') }}" min="0" step="0.01" required>
        @error('amount')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Closing Date</span>
        <input type="date" name="closing_date" value="{{ old('closing_date', optional($transaction->closing_date ?? null)->format('Y-m-d')) }}">
        @error('closing_date')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Description</span>
        <textarea name="description" rows="4">{{ old('description', $transaction->description ?? '') }}</textarea>
        @error('description')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
