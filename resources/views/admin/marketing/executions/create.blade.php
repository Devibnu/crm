@extends('admin.layouts.app')

@section('title', 'Add Campaign Execution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Add Campaign Execution - Krakatau CRM" data-doc-title-id="Tambah Eksekusi Campaign - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'execution'])
            </div>
            <div>
                <h1 data-lang-en="Add Campaign Execution" data-lang-id="Tambah Eksekusi Campaign">Add Campaign Execution</h1>
                <p data-lang-en="Create a campaign delivery execution and start tracking channel performance." data-lang-id="Buat eksekusi pengiriman campaign dan mulai tracking performa channel.">Buat eksekusi pengiriman campaign dan mulai tracking performa channel.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Execution" data-lang-id="Eksekusi Baru">New Execution</h2>
                    <p data-lang-en="Choose the campaign, audience segment, channel, timeline, and starting metrics." data-lang-id="Pilih campaign, segmen audiens, channel, timeline, dan metrik awal.">Pilih campaign, audience segment, channel, timeline, dan metrics awal.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.executions.store') }}">
                @csrf

                @include('admin.marketing.executions._form', [
                    'execution' => $execution,
                    'campaigns' => $campaigns,
                    'segments' => $segments,
                    'channelOptions' => $channelOptions,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.executions.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Execution" data-lang-id="Simpan Eksekusi">Save Execution</button>
                </div>
            </form>
        </article>
    </section>
@endsection
