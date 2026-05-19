@extends('admin.layouts.app')

@section('title', 'Add Audience Segment - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Add Audience Segment - Krakatau CRM" data-doc-title-id="Tambah Segmen Audiens - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'audience'])
            </div>
            <div>
                <h1 data-lang-en="Add Audience Segment" data-lang-id="Tambah Segmen Audiens">Add Audience Segment</h1>
                <p data-lang-en="Create a new audience segment for marketing campaign targeting." data-lang-id="Buat segmentasi audiens baru untuk penargetan campaign marketing.">Buat segmentasi audience baru untuk targeting campaign marketing.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Segment" data-lang-id="Segmen Baru">New Segment</h2>
                    <p data-lang-en="Define the segment type, audience estimate, and criteria JSON." data-lang-id="Tentukan tipe segmen, estimasi audiens, dan JSON kriteria.">Definisikan tipe segment, estimasi audience, dan criteria JSON.</p>
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
                    <a href="{{ route('admin.marketing.audiences.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Segment" data-lang-id="Simpan Segmen">Save Segment</button>
                </div>
            </form>
        </article>
    </section>
@endsection
