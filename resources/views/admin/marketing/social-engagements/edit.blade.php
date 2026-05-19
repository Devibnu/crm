@extends('admin.layouts.app')

@section('title', 'Edit Social Post - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Edit Social Post - Krakatau CRM" data-doc-title-id="Ubah Post Sosial - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'social'])
            </div>
            <div>
                <h1 data-lang-en="Edit Social Post" data-lang-id="Ubah Post Sosial">Edit Social Post</h1>
                <p data-lang-en="Update the content, publishing status, and social media engagement metrics." data-lang-id="Perbarui konten, status publikasi, dan metrik engagement social media.">Perbarui content, status publishing, dan metrics engagement social media.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $post->post_title }}</h2>
                    <p>{{ ucfirst($post->platform) }}</p>
                </div>
                <span class="status-badge status-{{ $post->status }}">{{ ucfirst($post->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.marketing.social-engagements.update', $post) }}">
                @csrf
                @method('PUT')

                @include('admin.marketing.social-engagements._form', [
                    'post' => $post,
                    'campaigns' => $campaigns,
                    'platformOptions' => $platformOptions,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.social-engagements.show', $post) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Post" data-lang-id="Perbarui Post">Update Post</button>
                </div>
            </form>
        </article>
    </section>
@endsection
