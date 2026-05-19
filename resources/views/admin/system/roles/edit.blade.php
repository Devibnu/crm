@extends('admin.layouts.app')

@section('title', 'Edit Role - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Role - Krakatau CRM" data-doc-title-id="Edit Role - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1 data-lang-en="Edit Role" data-lang-id="Edit Role">Edit Role</h1>
                <p>
                    <span data-lang-en="Update permissions for role" data-lang-id="Perbarui permission untuk role">Perbarui permission untuk role</span>
                    <strong>{{ $role->name }}</strong>.
                </p>
            </div>
        </article>

        @include('admin.system.roles.partials.alerts')

        <form method="POST" action="{{ route('admin.system.roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('admin.system.roles.partials.form', ['mode' => 'edit'])
        </form>
    </section>
@endsection
