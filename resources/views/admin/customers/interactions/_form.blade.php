@php
    $interaction = $interaction ?? null;
    $selectedCustomerId = old('customer_id', $interaction->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedType = old('type', $interaction->type ?? 'call');
    $interactionAt = old('interaction_at', optional($interaction->interaction_at ?? null)->format('Y-m-d\TH:i'));
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
        <span data-lang-en="Type" data-lang-id="Tipe">Type</span> <strong>*</strong>
        <select name="type" required>
            @foreach ($typeOptions as $type)
                <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
        @error('type')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Subject" data-lang-id="Subjek">Subject</span> <strong>*</strong>
        <input type="text" name="subject" value="{{ old('subject', $interaction->subject ?? '') }}" maxlength="255" required>
        @error('subject')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Description" data-lang-id="Deskripsi">Description</span>
        <textarea name="description" rows="4">{{ old('description', $interaction->description ?? '') }}</textarea>
        @error('description')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Interaction Date" data-lang-id="Tanggal Interaksi">Interaction Date</span>
        <input type="datetime-local" name="interaction_at" value="{{ $interactionAt }}">
        @error('interaction_at')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Handled By" data-lang-id="Ditangani Oleh">Handled By</span>
        <input type="text" name="handled_by" value="{{ old('handled_by', $interaction->handled_by ?? '') }}" maxlength="255">
        @error('handled_by')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Outcome" data-lang-id="Hasil">Outcome</span>
        <input type="text" name="outcome" value="{{ old('outcome', $interaction->outcome ?? '') }}" maxlength="255">
        @error('outcome')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
