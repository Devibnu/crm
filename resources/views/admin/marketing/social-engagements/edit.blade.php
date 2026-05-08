@extends('admin.layouts.app')

@section('title', 'Edit Social Post - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'social'])
            </div>
            <div>
                <h1>Edit Social Post</h1>
                <p>Perbarui content, status publishing, dan metrics engagement social media.</p>
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
                    <a href="{{ route('admin.marketing.social-engagements.show', $post) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Post</button>
                </div>
            </form>
        </article>
    </section>
@endsection
