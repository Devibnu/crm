@extends('admin.layouts.app')

@section('title', 'Edit Role - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card role-edit-header">
            <div class="service-card-icon role-header-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1>Kelola Permission Role</h1>
                <p>Atur hak akses untuk role "<strong>{{ $role->name }}</strong>" secara detail.</p>
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
