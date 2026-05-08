@extends('admin.layouts.app')

@section('title', 'Edit Campaign Execution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'execution'])
            </div>
            <div>
                <h1>Edit Campaign Execution</h1>
                <p>Perbarui status, timeline, dan metrics eksekusi campaign.</p>
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
                    <a href="{{ route('admin.marketing.executions.show', $execution) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Execution</button>
                </div>
            </form>
        </article>
    </section>
@endsection
