@extends('admin.layouts.app')

@section('title', 'Add Case Resolution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'case'])
            </div>
            <div>
                <h1>Add Case Resolution</h1>
                <p>Catat hasil penyelesaian tiket layanan pelanggan dan status notifikasi customer.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Case Resolution</h2>
                    <p>Pilih ticket lalu isi ringkasan, root cause, dan detail penyelesaian.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.case-resolutions.store') }}">
                @csrf

                @include('admin.service.case-resolutions._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.case-resolutions.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Resolution</button>
                </div>
            </form>
        </article>
    </section>
@endsection
