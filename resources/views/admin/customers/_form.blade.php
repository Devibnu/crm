@php
    $customer = $customer ?? null;
@endphp

<div class="customer-form-grid">
    <label class="field">
        <span data-lang-en="Name" data-lang-id="Nama">Name</span> <strong>*</strong>
        <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required>
        @error('name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Company Name" data-lang-id="Nama Perusahaan">Company Name</span>
        <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name ?? '') }}">
        @error('company_name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Email" data-lang-id="Email">Email</span>
        <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}">
        @error('email')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Phone" data-lang-id="Telepon">Phone</span>
        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}">
        @error('phone')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="WhatsApp" data-lang-id="WhatsApp">WhatsApp</span>
        <input type="text" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp ?? '') }}">
        @error('whatsapp')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Source" data-lang-id="Sumber">Source</span>
        <input type="text" name="source" value="{{ old('source', $customer->source ?? '') }}">
        @error('source')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Status" data-lang-id="Status">Status</span>
        <select name="status" required>
            @foreach ($statusOptions as $status)
                <option value="{{ $status }}" @selected(old('status', $customer->status ?? 'new') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Owner Name" data-lang-id="Nama Owner">Owner Name</span>
        <input type="text" name="owner_name" value="{{ old('owner_name', $customer->owner_name ?? '') }}">
        @error('owner_name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
        <textarea name="notes" rows="5">{{ old('notes', $customer->notes ?? '') }}</textarea>
        @error('notes')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
