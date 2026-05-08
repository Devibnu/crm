@extends('admin.layouts.app')

@section('title', 'Add Social Post - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'social'])
            </div>
            <div>
                <h1>Add Social Post</h1>
                <p>Buat post social media baru dan tracking metrics engagement.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Social Post</h2>
                    <p>Isi platform, content, publishing detail, dan engagement metrics.</p>
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
                    <a href="{{ route('admin.marketing.social-engagements.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Post</button>
                </div>
            </form>
        </article>
    </section>
@endsection
