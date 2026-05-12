@extends('admin.layouts.app')

@section('title', 'Edit Role - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1>Edit Role</h1>
                <p>Perbarui permission untuk role {{ $role->name }}.</p>
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
