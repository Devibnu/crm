@extends('admin.layouts.app')

@section('title', 'Add Automation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Add Automation - Krakatau CRM" data-doc-title-id="Tambah Otomasi - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'automation'])
            </div>
            <div>
                <h1 data-lang-en="Add Automation" data-lang-id="Tambah Otomasi">Add Automation</h1>
                <p data-lang-en="Create a new automation rule for lead nurturing and marketing follow-ups." data-lang-id="Buat aturan otomasi baru untuk nurturing lead dan tindak lanjut marketing.">Buat rule automation baru untuk nurturing lead dan follow-up marketing.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="New Automation" data-lang-id="Otomasi Baru">New Automation</h2>
                    <p data-lang-en="Choose the context, trigger, action, conditions, and rule payload." data-lang-id="Pilih konteks, trigger, action, conditions, dan payload aturan.">Pilih context, trigger, action, conditions, dan payload rule.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.marketing.automations.store') }}">
                @csrf

                @include('admin.marketing.automations._form', [
                    'automation' => $automation,
                    'campaigns' => $campaigns,
                    'segments' => $segments,
                    'triggerOptions' => $triggerOptions,
                    'actionOptions' => $actionOptions,
                    'statusOptions' => $statusOptions,
                    'conditionsJson' => $conditionsJson,
                    'actionPayloadJson' => $actionPayloadJson,
                ])

                <div class="form-actions">
                    <a href="{{ route('admin.marketing.automations.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Save Automation" data-lang-id="Simpan Otomasi">Save Automation</button>
                </div>
            </form>
        </article>
    </section>
@endsection
