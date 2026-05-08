@extends('admin.layouts.app')

@section('title', 'Add Landing Page - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'landing'])
            </div>
            <div>
                <h1>Add Landing Page</h1>
                <p>Buat landing page campaign dan form lead capture baru.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Landing Page</h2>
                    <p>Isi konten halaman, form builder, dan publishing detail.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.landing-pages.store') }}">
                @csrf

                @include('admin.marketing.landing-pages._form', [
                    'landingPage' => $landingPage,
                    'campaigns' => $campaigns,
                    'statusOptions' => $statusOptions,
                    'formFieldsJson' => $formFieldsJson,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.landing-pages.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Landing Page</button>
                </div>
            </form>
        </article>
    </section>
@endsection
