@extends('admin.layouts.app')

@section('title', 'Edit SLA Policy - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'timer'])
            </div>
            <div>
                <h1>Edit SLA Policy</h1>
                <p>Perbarui aturan response time, resolution time, priority, dan active status.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $policy->name }}</h2>
                    <p>{{ ucfirst($policy->priority) }} priority policy</p>
                </div>
                <span class="status-badge status-{{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span>
            </div>

            <form method="POST" action="{{ route('admin.service.sla.update', $policy) }}">
                @csrf
                @method('PUT')

                @include('admin.service.sla._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.sla.show', $policy) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update SLA Policy</button>
                </div>
            </form>
        </article>
    </section>
@endsection
