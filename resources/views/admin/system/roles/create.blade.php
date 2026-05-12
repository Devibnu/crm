@extends('admin.layouts.app')

@section('title', 'Create Role - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1>Create Role</h1>
                <p>Buat role baru dan pilih permission yang sesuai.</p>
            </div>
        </article>

        @include('admin.system.roles.partials.alerts')

        <form method="POST" action="{{ route('admin.system.roles.store') }}">
            @csrf
            @include('admin.system.roles.partials.form', ['mode' => 'create'])
        </form>
    </section>
@endsection
