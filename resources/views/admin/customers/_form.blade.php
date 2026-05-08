@php
    $customer = $customer ?? null;
@endphp

<div class="customer-form-grid">
    <label class="field">
        <span>Name <strong>*</strong></span>
        <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required>
        @error('name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Company Name</span>
        <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name ?? '') }}">
        @error('company_name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Email</span>
        <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}">
        @error('email')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Phone</span>
        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}">
        @error('phone')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>WhatsApp</span>
        <input type="text" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp ?? '') }}">
        @error('whatsapp')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Source</span>
        <input type="text" name="source" value="{{ old('source', $customer->source ?? '') }}">
        @error('source')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Status</span>
        <select name="status" required>
            @foreach ($statusOptions as $status)
                <option value="{{ $status }}" @selected(old('status', $customer->status ?? 'new') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Owner Name</span>
        <input type="text" name="owner_name" value="{{ old('owner_name', $customer->owner_name ?? '') }}">
        @error('owner_name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Notes</span>
        <textarea name="notes" rows="5">{{ old('notes', $customer->notes ?? '') }}</textarea>
        @error('notes')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
