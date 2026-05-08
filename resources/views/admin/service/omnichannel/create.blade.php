@extends('admin.layouts.app')

@section('title', 'Add Omnichannel Message - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'inbox'])
            </div>
            <div>
                <h1>Add Omnichannel Message</h1>
                <p>Catat message baru dari channel customer service.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Message</h2>
                    <p>Isi konteks channel, sender, content, assignment, dan status message.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.omnichannel.store') }}">
                @csrf

                @include('admin.service.omnichannel._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.omnichannel.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Message</button>
                </div>
            </form>
        </article>
    </section>
@endsection
