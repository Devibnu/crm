@extends('admin.layouts.app')

@section('title', 'Edit Interaction - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'mail'])
            </div>
            <div>
                <h1>Edit Interaction</h1>
                <p>Perbarui detail interaction agar histori customer tetap akurat.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.interactions.update', $interaction) }}">
                @csrf
                @method('PUT')

                @include('admin.customers.interactions._form', [
                    'interaction' => $interaction,
                    'customers' => $customers,
                    'typeOptions' => $typeOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.interactions') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Interaction</button>
                </div>
            </form>
        </article>
    </section>
@endsection
