@extends('admin.layouts.app')

@section('title', 'Add Sales Activity - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1>Add Sales Activity</h1>
                <p>Tambahkan aktivitas sales baru untuk lead, opportunity, atau customer.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Activity</h2>
                    <p>Hubungkan aktivitas ke data terkait, lalu isi informasi activity dan assignment.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.sales.activities.store') }}">
                @csrf

                @include('admin.sales.activities._form')

                <div class="form-actions">
                    <a href="{{ route('admin.sales.activities.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Activity</button>
                </div>
            </form>
        </article>
    </section>
@endsection
