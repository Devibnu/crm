@extends('admin.layouts.app')

@section('title', 'Edit User - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit User - Krakatau CRM" data-doc-title-id="Edit User - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'user'])
            </div>
            <div>
                <h1 data-lang-en="Edit User" data-lang-id="Edit User">Edit User</h1>
                <p data-lang-en="Update account data, role, and user credentials safely." data-lang-id="Perbarui data akun, role, dan credential user secara aman.">Perbarui data akun, role, dan credential user secara aman.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="card customer-alert danger">{{ session('error') }}</div>
        @endif

        <div class="card customer-alert info" data-lang-en="If the password does not need to change, leave the password field empty." data-lang-id="Jika password tidak perlu diganti, biarkan field password kosong.">
            Jika password tidak perlu diganti, biarkan field password kosong.
        </div>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.system.users.update', $user) }}">
                @csrf
                @method('PUT')

                @include('admin.system.users._form', ['user' => $user])

                <div class="form-actions">
                    <a href="{{ route('admin.system.users.show', $user) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update User" data-lang-id="Ubah User">Update User</button>
                </div>
            </form>
        </article>
    </section>
@endsection
