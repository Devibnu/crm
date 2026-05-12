@extends('admin.layouts.app')

@section('title', 'Edit WhatsApp Broadcast - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>Edit WhatsApp Broadcast</h1>
                <p>Perbarui campaign, audience source, dan template broadcast WhatsApp.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $broadcast->name }}</h2>
                    <p>{{ $broadcast->marketingCampaign?->name ?: 'Without campaign' }}</p>
                </div>
                <span class="status-badge status-{{ $broadcast->status }}">{{ ucfirst($broadcast->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.update', $broadcast) }}">
                @csrf
                @method('PUT')

                @include('admin.marketing.whatsapp-broadcasts._form', [
                    'broadcast' => $broadcast,
                    'campaigns' => $campaigns,
                    'statusOptions' => $statusOptions,
                    'targetTypeOptions' => $targetTypeOptions,
                    'recipientTypeOptions' => $recipientTypeOptions,
                    'defaultRecipientType' => $defaultRecipientType,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.show', $broadcast) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Broadcast</button>
                </div>
            </form>
        </article>
    </section>
@endsection
