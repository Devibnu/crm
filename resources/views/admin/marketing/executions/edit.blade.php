@extends('admin.layouts.app')

@section('title', 'Edit Campaign Execution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Edit Campaign Execution - Krakatau CRM" data-doc-title-id="Ubah Eksekusi Campaign - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'execution'])
            </div>
            <div>
                <h1 data-lang-en="Edit Campaign Execution" data-lang-id="Ubah Eksekusi Campaign">Edit Campaign Execution</h1>
                <p data-lang-en="Update the status, timeline, and metrics of the campaign execution." data-lang-id="Perbarui status, timeline, dan metrik eksekusi campaign.">Perbarui status, timeline, dan metrics eksekusi campaign.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $execution->execution_name }}</h2>
                    <p>{{ ucwords(str_replace('_', ' ', $execution->channel)) }}</p>
                </div>
                <span class="status-badge status-{{ $execution->status }}">{{ ucfirst($execution->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.marketing.executions.update', $execution) }}">
                @csrf
                @method('PUT')

                @include('admin.marketing.executions._form', [
                    'execution' => $execution,
                    'campaigns' => $campaigns,
                    'segments' => $segments,
                    'channelOptions' => $channelOptions,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.executions.show', $execution) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Execution" data-lang-id="Perbarui Eksekusi">Update Execution</button>
                </div>
            </form>
        </article>
    </section>
@endsection
