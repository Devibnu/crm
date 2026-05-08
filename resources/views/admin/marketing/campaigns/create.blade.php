@extends('admin.layouts.app')

@section('title', 'Add Campaign - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'campaign'])
            </div>
            <div>
                <h1>Add Campaign</h1>
                <p>Buat campaign marketing baru dengan target audience, budget, dan timeline yang jelas.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Campaign</h2>
                    <p>Isi informasi utama campaign, target leads, dan channel aktivasi.</p>
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
                    <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Campaign</button>
                </div>
            </form>
        </article>
    </section>
@endsection
