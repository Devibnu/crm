@extends('admin.layouts.app')

@section('title', $title.' - Krakatau CRM')

@section('content')
    <section class="service-page">
        <article class="card service-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => $icon])
            </div>
            <div>
                <span class="service-badge">Coming Soon</span>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
            </div>
        </article>
    </section>
@endsection
