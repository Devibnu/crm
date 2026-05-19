@extends('admin.layouts.app')

@section('title', 'Add Campaign - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Add Campaign - Krakatau CRM" data-doc-title-id="Tambah Campaign - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'campaign'])
            </div>
            <div>
                <h1 data-lang-en="Add Campaign" data-lang-id="Tambah Campaign">Add Campaign</h1>
                <p data-lang-en="Create a new marketing campaign with a clear target audience, budget, and timeline." data-lang-id="Buat campaign marketing baru dengan target audiens, anggaran, dan timeline yang jelas.">Buat campaign marketing baru dengan target audience, budget, dan timeline yang jelas.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Campaign" data-lang-id="Campaign Baru">New Campaign</h2>
                    <p data-lang-en="Fill in the main campaign details, lead targets, and activation channels." data-lang-id="Isi informasi utama campaign, target lead, dan channel aktivasi.">Isi informasi utama campaign, target leads, dan channel aktivasi.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.campaigns.store') }}">
                @csrf

                @include('admin.marketing.campaigns._form', [
                    'campaign' => $campaign,
                    'typeOptions' => $typeOptions,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Campaign" data-lang-id="Simpan Campaign">Save Campaign</button>
                </div>
            </form>
        </article>
    </section>
@endsection
