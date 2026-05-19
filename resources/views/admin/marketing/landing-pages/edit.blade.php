@extends('admin.layouts.app')

@section('title', 'Edit Landing Page - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Edit Landing Page - Krakatau CRM" data-doc-title-id="Ubah Landing Page - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'landing'])
            </div>
            <div>
                <h1 data-lang-en="Edit Landing Page" data-lang-id="Ubah Landing Page">Edit Landing Page</h1>
                <p data-lang-en="Update the campaign landing page, form fields, and publishing status." data-lang-id="Perbarui landing page campaign, field formulir, dan status publikasi.">Perbarui landing page campaign, form fields, dan status publishing.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $landingPage->title }}</h2>
                    <p>{{ $landingPage->slug }}</p>
                </div>
                <span class="status-badge status-{{ $landingPage->status }}">{{ ucfirst($landingPage->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.marketing.landing-pages.update', $landingPage) }}">
                @csrf
                @method('PUT')

                @include('admin.marketing.landing-pages._form', [
                    'landingPage' => $landingPage,
                    'campaigns' => $campaigns,
                    'statusOptions' => $statusOptions,
                    'formFieldsJson' => $formFieldsJson,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.landing-pages.show', $landingPage) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Landing Page" data-lang-id="Perbarui Landing Page">Update Landing Page</button>
                </div>
            </form>
        </article>
    </section>
@endsection
