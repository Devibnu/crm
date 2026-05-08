@extends('admin.layouts.app')

@section('title', 'Add Lead - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <h1>Add Lead</h1>
                <p>Tambahkan lead baru untuk proses assignment dan kualifikasi.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <form method="POST" action="{{ route('admin.sales.leads.store') }}">
                @csrf

                @include('admin.sales.leads._form', [
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'priorityOptions' => $priorityOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.sales.leads') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Lead</button>
                </div>
            </form>
        </article>
    </section>
@endsection
