@extends('admin.layouts.app')

@section('title', 'Tambah Menu - Krakatau CRM')

@section('content')
<span hidden data-doc-title-en="Add Menu - Krakatau CRM" data-doc-title-id="Tambah Menu - Krakatau CRM"></span>
<section class="service-page customer-list-page">
    <article class="card service-card customer-list-card">
        <div class="service-card-icon">
            @include('admin.partials.sidebar-icon', ['icon' => 'menu'])
        </div>
        <div>
            <h1 data-lang-en="Add Dynamic Menu" data-lang-id="Tambah Menu Dinamis">Add Dynamic Menu</h1>
            <p data-lang-en="Create a new menu for the Vuexy sidebar, mobile drawer, and bottom navigation." data-lang-id="Buat menu baru untuk sidebar Vuexy, mobile drawer, dan bottom navigation.">Buat menu baru untuk sidebar Vuexy, mobile drawer, dan bottom navigation.</p>
        </div>
    </article>

    @if ($errors->any())
        <div class="card customer-alert danger" data-lang-en="Please review the menu input and role visibility." data-lang-id="Periksa kembali input menu dan role visibility.">Periksa kembali input menu dan role visibility.</div>
    @endif

    <form method="POST" action="{{ route('admin.system.menus.store') }}" class="card customer-form-card">
        @csrf
        @include('admin.system.menus._form')

        <div class="customer-form-actions">
            <a href="{{ route('admin.system.menus.index') }}" class="btn btn-muted" data-lang-en="Cancel" data-lang-id="Batal">Cancel</a>
            <button type="submit" class="btn btn-primary" data-lang-en="Save Menu" data-lang-id="Simpan Menu">Save Menu</button>
        </div>
    </form>
</section>
@endsection
