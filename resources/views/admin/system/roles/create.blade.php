@extends('admin.layouts.app')

@section('title', 'Create Role - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Create Role - Krakatau CRM" data-doc-title-id="Buat Role - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1 data-lang-en="Create Role" data-lang-id="Buat Role">Create Role</h1>
                <p data-lang-en="Create a new role and choose the right permissions." data-lang-id="Buat role baru dan pilih permission yang sesuai.">Buat role baru dan pilih permission yang sesuai.</p>
            </div>
        </article>

        @include('admin.system.roles.partials.alerts')

        <form method="POST" action="{{ route('admin.system.roles.store') }}">
            @csrf
            @include('admin.system.roles.partials.form', ['mode' => 'create'])
        </form>
    </section>
@endsection
