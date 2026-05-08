@extends('admin.layouts.app')

@section('title', 'Edit Behavior - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1>Edit Behavior</h1>
                <p>Perbarui data behavior agar profil customer lebih akurat.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.behavior.update', $behavior) }}">
                @csrf
                @method('PUT')

                @include('admin.customers.behavior_crud._form', [
                    'behavior' => $behavior,
                    'customers' => $customers,
                    'lifecycleStageOptions' => $lifecycleStageOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Behavior</button>
                </div>
            </form>
        </article>
    </section>
@endsection
