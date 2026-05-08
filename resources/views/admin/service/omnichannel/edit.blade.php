@extends('admin.layouts.app')

@section('title', 'Edit Omnichannel Message - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'inbox'])
            </div>
            <div>
                <h1>Edit Omnichannel Message</h1>
                <p>Perbarui channel, sender, assignment, dan status message.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $message->subject ?: ($message->sender_name ?: 'Omnichannel Message') }}</h2>
                    <p>{{ ucfirst($message->channel) }} / {{ ucfirst($message->direction) }}</p>
                </div>
                <span class="status-badge status-{{ $message->status }}">{{ ucfirst(str_replace('_', ' ', $message->status)) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.service.omnichannel.update', $message) }}">
                @csrf
                @method('PUT')

                @include('admin.service.omnichannel._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.omnichannel.show', $message) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Message</button>
                </div>
            </form>
        </article>
    </section>
@endsection
