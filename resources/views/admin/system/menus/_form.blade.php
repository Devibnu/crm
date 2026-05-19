@php
    $menu = $menu ?? null;
    $selectedParent = old('parent_id', $menu?->parent_id);
    $selectedSection = old('section', $menu?->section ?? 'dashboard');
    $selectedRoles = collect($selectedRoles ?? [])->map(fn ($value) => (string) $value)->all();
    $isActive = (bool) old('is_active', $menu?->is_active ?? true);
@endphp

<div class="customer-form-grid">
    <label class="field">
        <span data-lang-en="Menu Title" data-lang-id="Judul Menu">Menu Title</span> <strong>*</strong>
        <input type="text" name="title" value="{{ old('title', $menu->title ?? '') }}" maxlength="255" required>
        @error('title')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Section" data-lang-id="Section">Section</span> <strong>*</strong>
        <select name="section" required>
            @foreach ($sectionOptions as $value => $label)
                <option value="{{ $value }}" @selected($selectedSection === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('section')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Route" data-lang-id="Route">Route</span>
        <input type="text" name="route" value="{{ old('route', $menu->route ?? '') }}" maxlength="255" placeholder="/sales/leads" data-placeholder-en="/sales/leads" data-placeholder-id="/sales/leads">
        <small data-lang-en="Leave blank if this menu only acts as a parent group." data-lang-id="Kosongkan jika menu hanya berfungsi sebagai group parent.">Leave blank if this menu only acts as a parent group.</small>
        @error('route')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Icon" data-lang-id="Icon">Icon</span>
        <input type="text" name="icon" value="{{ old('icon', $menu->icon ?? '') }}" maxlength="255" placeholder="tabler-layout-dashboard" data-placeholder-en="tabler-layout-dashboard" data-placeholder-id="tabler-layout-dashboard">
        <small data-lang-en="Use a Tabler/Lucide icon string for the Vuexy frontend." data-lang-id="Gunakan nama icon Tabler/Lucide string untuk frontend Vuexy.">Use a Tabler/Lucide icon string for the Vuexy frontend.</small>
        @error('icon')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Parent Menu" data-lang-id="Menu Parent">Parent Menu</span>
        <select name="parent_id">
            <option value="" data-lang-en="Root menu" data-lang-id="Menu root">Root menu</option>
            @foreach ($parentOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) $selectedParent === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('parent_id')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Sort Order" data-lang-id="Urutan">Sort Order</span> <strong>*</strong>
        <input type="number" name="sort_order" value="{{ old('sort_order', $menu->sort_order ?? 10) }}" min="0" max="9999" required>
        @error('sort_order')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full preference-consent-field">
        <span data-lang-en="Menu Status" data-lang-id="Status Menu">Menu Status</span>
        <input type="hidden" name="is_active" value="0">
        <label class="preference-checkbox">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            <span data-lang-en="Enable this menu in the application navigation" data-lang-id="Aktifkan menu ini di navigasi aplikasi">Enable this menu in the application navigation</span>
        </label>
        @error('is_active')<small class="error">{{ $message }}</small>@enderror
    </label>

    <div class="field field-full">
        <span data-lang-en="Role Visibility" data-lang-id="Role Visibility">Role Visibility</span>
        <div class="menu-role-grid">
            @foreach ($roles as $role)
                <label class="menu-role-option">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array((string) $role->id, $selectedRoles, true))>
                    <span>{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
        <small data-lang-en="If no role is selected, this menu will be visible to all roles that can log in." data-lang-id="Jika tidak ada role dipilih, menu akan dianggap visible untuk semua role yang bisa login.">If no role is selected, this menu will be visible to all roles that can log in.</small>
        @error('roles')<small class="error">{{ $message }}</small>@enderror
        @error('roles.*')<small class="error">{{ $message }}</small>@enderror
    </div>
</div>
