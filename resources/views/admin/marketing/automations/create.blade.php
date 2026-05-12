@extends('admin.layouts.app')

@section('title', 'Add Automation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'automation'])
            </div>
            <div>
                <h1>Add Automation</h1>
                <p>Buat rule automation baru untuk nurturing lead dan follow-up marketing.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Automation</h2>
                    <p>Pilih context, trigger, action, conditions, dan payload rule.</p>
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
                    <a href="{{ route('admin.marketing.automations.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Automation</button>
                </div>
            </form>
        </article>
    </section>
@endsection
