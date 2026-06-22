@extends('admin.layouts.app')

@section('title', 'Edit Opportunity - Krakatau CRM')

@section('content')
    @php
        $updatedStage = (string) request()->query('stage_updated', '');
        $updatedStageLabel = in_array($updatedStage, $statusOptions, true)
            ? ($statusLabels[$updatedStage] ?? ucfirst($updatedStage))
            : null;
    @endphp

    <section class="lead-form-page opportunity-form-page">
        <header class="lead-list-header lead-form-banner">
            <div>
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Edit Opportunity</h1>
                <p>Perbarui data opportunity agar pipeline dan estimasi closing tetap akurat.</p>
            </div>
            <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-sm lead-banner-secondary">Back</a>
        </header>

        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        @if ($updatedStageLabel)
            <div class="customer-alert success">Opportunity berhasil dipindahkan ke stage {{ $updatedStageLabel }}. Silakan lengkapi data opportunity.</div>
        @endif

        <form method="POST" action="{{ route('admin.sales.opportunities.update', $opportunity) }}" class="lead-workspace-form opportunity-workspace-form">
            @csrf
            @method('PUT')

            <section class="opportunity-form-panel">
                @include('admin.sales.opportunities._form', [
                    'opportunity' => $opportunity,
                    'leads' => $leads,
                    'customers' => $customers,
                    'statusOptions' => $statusOptions,
                    'statusLabels' => $statusLabels,
                ])
            </section>

            <div class="lead-form-actions">
                <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Opportunity</button>
            </div>
        </form>
    </section>
@endsection
