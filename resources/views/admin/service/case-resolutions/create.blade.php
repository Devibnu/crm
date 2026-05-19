@extends('admin.layouts.app')

@section('title', 'Add Case Resolution - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Add Case Resolution - Krakatau CRM" data-doc-title-id="Tambah Penyelesaian Kasus - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'case'])
            </div>
            <div>
                <h1 data-lang-en="Add Case Resolution" data-lang-id="Tambah Penyelesaian Kasus">Add Case Resolution</h1>
                <p data-lang-en="Record customer service ticket resolution results and customer notification status." data-lang-id="Catat hasil penyelesaian tiket layanan pelanggan dan status notifikasi customer.">Catat hasil penyelesaian tiket layanan pelanggan dan status notifikasi customer.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Case Resolution" data-lang-id="Penyelesaian Kasus Baru">New Case Resolution</h2>
                    <p data-lang-en="Select a ticket, then fill in the summary, root cause, and resolution details." data-lang-id="Pilih ticket lalu isi ringkasan, root cause, dan detail penyelesaian.">Pilih ticket lalu isi ringkasan, root cause, dan detail penyelesaian.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.case-resolutions.store') }}">
                @csrf

                @include('admin.service.case-resolutions._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.case-resolutions.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Resolution" data-lang-id="Simpan Penyelesaian">Save Resolution</button>
                </div>
            </form>
        </article>
    </section>
@endsection
