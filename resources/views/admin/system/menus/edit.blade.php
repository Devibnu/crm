@extends('admin.layouts.app')

@section('title', 'Edit Menu - Krakatau CRM')

@section('content')
<span hidden data-doc-title-en="Edit Menu - Krakatau CRM" data-doc-title-id="Edit Menu - Krakatau CRM"></span>
<section class="service-page customer-list-page">
    <article class="card service-card customer-list-card">
        <div class="service-card-icon">
            @include('admin.partials.sidebar-icon', ['icon' => 'menu'])
        </div>
        <div>
            <h1 data-lang-en="Edit Dynamic Menu" data-lang-id="Edit Menu Dinamis">Edit Dynamic Menu</h1>
            <p><span data-lang-en="Update menu structure, route, icon, and role access for" data-lang-id="Perbarui struktur menu, route, icon, dan role access untuk">Update menu structure, route, icon, and role access for</span> {{ $menu->title }}.</p>
        </div>
    </article>

    @if (session('error'))
        <div class="card customer-alert danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="card customer-alert danger" data-lang-en="Please review the menu input and role visibility." data-lang-id="Periksa kembali input menu dan role visibility.">Periksa kembali input menu dan role visibility.</div>
    @endif

    <form method="POST" action="{{ route('admin.system.menus.update', $menu) }}" class="card customer-form-card">
        @csrf
        @method('PUT')
        @include('admin.system.menus._form')

        <div class="customer-form-actions">
            <a href="{{ route('admin.system.menus.index') }}" class="btn btn-muted" data-lang-en="Cancel" data-lang-id="Batal">Cancel</a>
            <button type="submit" class="btn btn-primary" data-lang-en="Update Menu" data-lang-id="Ubah Menu">Update Menu</button>
        </div>
    </form>
</section>
@endsection
