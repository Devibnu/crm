@extends('admin.layouts.app')

@section('title', 'Add User - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add User - Krakatau CRM" data-doc-title-id="Tambah User - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'user'])
            </div>
            <div>
                <h1 data-lang-en="Add User" data-lang-id="Tambah User">Add User</h1>
                <p data-lang-en="Create a new user account and assign the right CRM access role." data-lang-id="Tambahkan akun user baru dan tentukan role aksesnya ke CRM.">Tambahkan akun user baru dan tentukan role aksesnya ke CRM.</p>
            </div>
        </article>

        @if (session('error'))
            <div class="card customer-alert danger">{{ session('error') }}</div>
        @endif

        <div class="card customer-alert info" data-lang-en="Initial password must be at least 8 characters. The user's role will immediately determine accessible menus and features." data-lang-id="Password awal minimal 8 karakter. Role user akan langsung menentukan menu dan fitur yang bisa diakses.">
            Password awal minimal 8 karakter. Role user akan langsung menentukan menu dan fitur yang bisa diakses.
        </div>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.system.users.store') }}">
                @csrf

                @include('admin.system.users._form', ['user' => $user])

                <div class="form-actions">
                    <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save User" data-lang-id="Simpan User">Save User</button>
                </div>
            </form>
        </article>
    </section>
@endsection
