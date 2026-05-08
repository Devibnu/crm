@extends('admin.layouts.app')

@section('title', 'Add Campaign Execution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'execution'])
            </div>
            <div>
                <h1>Add Campaign Execution</h1>
                <p>Buat eksekusi pengiriman campaign dan mulai tracking performa channel.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Execution</h2>
                    <p>Pilih campaign, audience segment, channel, timeline, dan metrics awal.</p>
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
                    <a href="{{ route('admin.marketing.executions.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Execution</button>
                </div>
            </form>
        </article>
    </section>
@endsection
