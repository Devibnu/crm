@extends('admin.layouts.app')

@section('title', 'Add Social Post - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Add Social Post - Krakatau CRM" data-doc-title-id="Tambah Post Sosial - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'social'])
            </div>
            <div>
                <h1 data-lang-en="Add Social Post" data-lang-id="Tambah Post Sosial">Add Social Post</h1>
                <p data-lang-en="Create a new social media post and track engagement metrics." data-lang-id="Buat post social media baru dan tracking metrik engagement.">Buat post social media baru dan tracking metrics engagement.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Social Post" data-lang-id="Post Sosial Baru">New Social Post</h2>
                    <p data-lang-en="Fill in the platform, content, publishing details, and engagement metrics." data-lang-id="Isi platform, konten, detail publikasi, dan metrik engagement.">Isi platform, content, publishing detail, dan engagement metrics.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.social-engagements.store') }}">
                @csrf

                @include('admin.marketing.social-engagements._form', [
                    'post' => $post,
                    'campaigns' => $campaigns,
                    'platformOptions' => $platformOptions,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.social-engagements.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Post" data-lang-id="Simpan Post">Save Post</button>
                </div>
            </form>
        </article>
    </section>
@endsection
