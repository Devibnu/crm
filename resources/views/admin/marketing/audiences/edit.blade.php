@extends('admin.layouts.app')

@section('title', 'Edit Audience Segment - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'audience'])
            </div>
            <div>
                <h1>Edit Audience Segment</h1>
                <p>Perbarui segmentasi audience dan criteria targeting.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $segment->name }}</h2>
                    <p>{{ ucfirst($segment->type) }}</p>
                </div>
                <span class="status-badge status-{{ $segment->status }}">{{ ucfirst($segment->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.marketing.audiences.update', $segment) }}">
                @csrf
                @method('PUT')

                @include('admin.marketing.audiences._form', [
                    'segment' => $segment,
                    'typeOptions' => $typeOptions,
                    'statusOptions' => $statusOptions,
                    'criteriaJson' => $criteriaJson,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.audiences.show', $segment) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Segment</button>
                </div>
            </form>
        </article>
    </section>
@endsection
