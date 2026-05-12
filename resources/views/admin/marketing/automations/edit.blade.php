@extends('admin.layouts.app')

@section('title', 'Edit Automation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'automation'])
            </div>
            <div>
                <h1>Edit Automation</h1>
                <p>Perbarui rule automation, trigger, action, dan JSON payload.</p>
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
                    <a href="{{ route('admin.marketing.automations.show', $automation) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Automation</button>
                </div>
            </form>
        </article>
    </section>
@endsection
