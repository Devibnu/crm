@extends('admin.layouts.app')

@section('title', 'Create Role - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page role-create-page">
        <header class="lead-list-header users-page-header">
            <div>
                <span class="crm-record-kicker">System Management</span>
                <h1>Create Role</h1>
                <p>Buat role baru dan pilih akses menu/fitur yang boleh digunakan.</p>
            </div>
            <a href="{{ route('admin.system.roles.index') }}" class="btn btn-primary">Back to Roles</a>
        </header>

        @include('admin.system.roles.partials.alerts')

        <form method="POST" action="{{ route('admin.system.roles.store') }}">
            @csrf
            @include('admin.system.roles.partials.form', ['mode' => 'create'])
        </form>
    </section>
@endsection
