@extends('admin.layouts.app')

@section('title', 'Add WhatsApp Broadcast - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>Add WhatsApp Broadcast</h1>
                <p>Buat campaign broadcast WhatsApp dan generate recipients dari customer atau lead.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Broadcast</h2>
                    <p>Simpan broadcast untuk membangun recipient list dan tracking status real.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.store') }}">
                @csrf

                @include('admin.marketing.whatsapp-broadcasts._form', [
                    'broadcast' => $broadcast,
                    'campaigns' => $campaigns,
                    'statusOptions' => $statusOptions,
                    'targetTypeOptions' => $targetTypeOptions,
                    'recipientTypeOptions' => $recipientTypeOptions,
                    'defaultRecipientType' => $defaultRecipientType,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Broadcast</button>
                </div>
            </form>
        </article>
    </section>
@endsection
