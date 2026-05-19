@extends('admin.layouts.app')

@section('title', 'Add Landing Page - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Add Landing Page - Krakatau CRM" data-doc-title-id="Tambah Landing Page - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'landing'])
            </div>
            <div>
                <h1 data-lang-en="Add Landing Page" data-lang-id="Tambah Landing Page">Add Landing Page</h1>
                <p data-lang-en="Create a new campaign landing page and lead capture form." data-lang-id="Buat landing page campaign dan formulir lead capture baru.">Buat landing page campaign dan form lead capture baru.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Landing Page" data-lang-id="Landing Page Baru">New Landing Page</h2>
                    <p data-lang-en="Fill in the page content, form builder, and publishing details." data-lang-id="Isi konten halaman, form builder, dan detail publikasi.">Isi konten halaman, form builder, dan publishing detail.</p>
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
                    <a href="{{ route('admin.marketing.landing-pages.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Landing Page" data-lang-id="Simpan Landing Page">Save Landing Page</button>
                </div>
            </form>
        </article>
    </section>
@endsection
