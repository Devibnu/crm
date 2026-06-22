@extends('admin.layouts.app')

@section('title', 'Add User - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add User - Krakatau CRM" data-doc-title-id="Tambah User - Krakatau CRM"></span>
    <section class="users-form-page">
        <header class="lead-list-header users-page-header">
            <div>
                <span class="crm-record-kicker">System Management</span>
                <h1>Create User</h1>
                <p>Tambah akun login dan role pengguna Krakatau CRM.</p>
            </div>
            <a href="{{ route('admin.system.users.index') }}" class="btn btn-primary">Back to Users</a>
        </header>

        @if (session('error'))
            <div class="card customer-alert danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.system.users.store') }}" class="users-form-shell">
            @csrf

            @include('admin.system.users._form', ['user' => $user])

            <div class="users-form-actions">
                <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted">Cancel</a>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </section>
@endsection
