@extends('admin.layouts.app')

@section('title', 'Add WhatsApp Broadcast - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Add WhatsApp Broadcast - Krakatau CRM" data-doc-title-id="Tambah Broadcast WhatsApp - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1 data-lang-en="Add WhatsApp Broadcast" data-lang-id="Tambah Broadcast WhatsApp">Add WhatsApp Broadcast</h1>
                <p data-lang-en="Create a WhatsApp broadcast campaign and generate recipients from customers or leads." data-lang-id="Buat campaign broadcast WhatsApp dan generate recipient dari customer atau lead.">Buat campaign broadcast WhatsApp dan generate recipients dari customer atau lead.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Broadcast" data-lang-id="Broadcast Baru">New Broadcast</h2>
                    <p data-lang-en="Save the broadcast to build the recipient list and track live status." data-lang-id="Simpan broadcast untuk membangun daftar recipient dan tracking status real.">Simpan broadcast untuk membangun recipient list dan tracking status real.</p>
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
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Broadcast" data-lang-id="Simpan Broadcast">Save Broadcast</button>
                </div>
            </form>
        </article>
    </section>
@endsection
