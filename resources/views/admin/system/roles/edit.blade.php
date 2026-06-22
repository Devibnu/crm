@extends('admin.layouts.app')

@section('title', 'Edit Role - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page role-edit-page">
        <header class="lead-list-header users-page-header">
            <div>
                <span class="crm-record-kicker">System Management</span>
                <h1>Edit Role</h1>
                <p>Atur akses menu dan fitur untuk role {{ $role->name }}.</p>
            </div>
            <a href="{{ route('admin.system.roles.show', $role) }}" class="btn btn-primary">Back to Role</a>
        </header>

        @include('admin.system.roles.partials.alerts')

        <form method="POST" action="{{ route('admin.system.roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('admin.system.roles.partials.form', ['mode' => 'edit'])
        </form>
    </section>
@endsection
