@php
    $transaction = $transaction ?? null;
    $selectedCustomerId = old('customer_id', $transaction->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedStatus = old('status', $transaction->status ?? 'pending');
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
        <span data-lang-en="Status" data-lang-id="Status">Status</span> <strong>*</strong>
        <select name="status" required>
            @foreach ($statusOptions as $status)
                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Title" data-lang-id="Judul">Title</span> <strong>*</strong>
        <input type="text" name="title" value="{{ old('title', $transaction->title ?? '') }}" maxlength="255" required>
        @error('title')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Amount" data-lang-id="Nominal">Amount</span> <strong>*</strong>
        <input type="number" name="amount" value="{{ old('amount', $transaction->amount ?? '') }}" min="0" step="0.01" required>
        @error('amount')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Closing Date" data-lang-id="Tanggal Closing">Closing Date</span>
        <input type="date" name="closing_date" value="{{ old('closing_date', optional($transaction->closing_date ?? null)->format('Y-m-d')) }}">
        @error('closing_date')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Description" data-lang-id="Deskripsi">Description</span>
        <textarea name="description" rows="4">{{ old('description', $transaction->description ?? '') }}</textarea>
        @error('description')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
