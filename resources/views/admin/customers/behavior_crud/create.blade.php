@extends('admin.layouts.app')

@section('title', 'Add Behavior - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1>Add Behavior</h1>
                <p>Tambahkan data behavior customer untuk insight lifecycle dan engagement.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.customers.behavior.store', $selectedCustomer) }}">
                @csrf

                @include('admin.customers.behavior_crud._form', [
                    'customers' => $customers,
                    'lifecycleStageOptions' => $lifecycleStageOptions,
                    'selectedCustomer' => $selectedCustomer,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.customers.behavior') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Behavior</button>
                </div>
            </form>
        </article>
    </section>
@endsection
