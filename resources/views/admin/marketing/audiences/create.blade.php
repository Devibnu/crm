@extends('admin.layouts.app')

@section('title', 'Add Audience Segment - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'audience'])
            </div>
            <div>
                <h1>Add Audience Segment</h1>
                <p>Buat segmentasi audience baru untuk targeting campaign marketing.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Segment</h2>
                    <p>Definisikan tipe segment, estimasi audience, dan criteria JSON.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.audiences.store') }}">
                @csrf

                @include('admin.marketing.audiences._form', [
                    'segment' => $segment,
                    'typeOptions' => $typeOptions,
                    'statusOptions' => $statusOptions,
                    'criteriaJson' => $criteriaJson,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.audiences.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Segment</button>
                </div>
            </form>
        </article>
    </section>
@endsection
