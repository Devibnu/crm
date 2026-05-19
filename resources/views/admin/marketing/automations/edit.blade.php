@extends('admin.layouts.app')

@section('title', 'Edit Automation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Edit Automation - Krakatau CRM" data-doc-title-id="Ubah Otomasi - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'automation'])
            </div>
            <div>
                <h1 data-lang-en="Edit Automation" data-lang-id="Ubah Otomasi">Edit Automation</h1>
                <p data-lang-en="Update automation rules, triggers, actions, and JSON payloads." data-lang-id="Perbarui aturan otomasi, trigger, action, dan payload JSON.">Perbarui rule automation, trigger, action, dan JSON payload.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $automation->name }}</h2>
                    <p>{{ ucwords(str_replace('_', ' ', $automation->trigger_type)) }}</p>
                </div>
                <span class="status-badge status-{{ $automation->status }}">{{ ucfirst($automation->status) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.marketing.automations.update', $automation) }}">
                @csrf
                @method('PUT')

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
                    <a href="{{ route('admin.marketing.automations.show', $automation) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Automation" data-lang-id="Perbarui Otomasi">Update Automation</button>
                </div>
            </form>
        </article>
    </section>
@endsection
