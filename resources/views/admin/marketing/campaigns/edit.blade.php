@extends('admin.layouts.app')

@section('title', 'Edit Campaign - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'campaign'])
            </div>
            <div>
                <h1>Edit Campaign</h1>
                <p>Perbarui campaign marketing, budget, target leads, dan timeline.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $campaign->name }}</h2>
                    <p>{{ ucwords(str_replace('_', ' ', $campaign->type)) }}</p>
                </div>
                <span class="status-badge status-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.marketing.campaigns.update', $campaign) }}">
                @csrf
                @method('PUT')

                @include('admin.marketing.campaigns._form', [
                    'campaign' => $campaign,
                    'typeOptions' => $typeOptions,
                    'statusOptions' => $statusOptions,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Campaign</button>
                </div>
            </form>
        </article>
    </section>
@endsection
