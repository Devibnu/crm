@php
    $selectedCustomerId = old('customer_id', $project->customer_id ?? '');
    $selectedLeadId = old('lead_id', $project->lead_id ?? '');
    $selectedOpportunityId = old('opportunity_id', $project->opportunity_id ?? '');
    $selectedQuotationId = old('quotation_id', $project->quotation_id ?? '');
    $selectedStatus = old('status', $project->status ?? 'planning');
    $selectedProjectManagerId = old('project_manager_id', $project->project_manager_id ?? '');
@endphp

@if ($sourceQuotation)
    <div class="card customer-alert success">
        Project source: Deal Won {{ $sourceQuotation->quote_number }}.
    </div>
@endif

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Source Records</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Customer</span>
                <select name="customer_id">
                    <option value="">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Lead</span>
                <select name="lead_id">
                    <option value="">Tanpa lead</option>
                    @foreach ($leads as $lead)
                        <option value="{{ $lead->id }}" @selected((string) $selectedLeadId === (string) $lead->id)>{{ $lead->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Opportunity</span>
                <select name="opportunity_id">
                    <option value="">Tanpa opportunity</option>
                    @foreach ($opportunities as $opportunity)
                        <option value="{{ $opportunity->id }}" @selected((string) $selectedOpportunityId === (string) $opportunity->id)>{{ $opportunity->title }}</option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Quotation</span>
                <select name="quotation_id">
                    <option value="">Tanpa quotation</option>
                    @foreach ($quotations as $quotation)
                        <option value="{{ $quotation->id }}" @selected((string) $selectedQuotationId === (string) $quotation->id)>{{ $quotation->quote_number }} - {{ $quotation->title }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Project Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Title <strong>*</strong></span>
                <input type="text" name="title" value="{{ old('title', $project->title ?? '') }}" maxlength="255" required>
                @error('title')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Budget</span>
                <input type="number" name="budget" value="{{ old('budget', $project->budget ?? 0) }}" min="0" step="0.01">
                @error('budget')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Status</span>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Project Manager</span>
                <select name="project_manager_id">
                    <option value="">Belum ditentukan</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $selectedProjectManagerId === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                @error('project_manager_id')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <p class="sales-form-hint">Progress dihitung otomatis dari milestone selesai dibagi total milestone.</p>
    </div>

    <div class="sales-form-section">
        <h2>Timeline & Description</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Start Date</span>
                <input type="date" name="start_date" value="{{ old('start_date', optional($project->start_date ?? null)->format('Y-m-d')) }}">
                @error('start_date')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Due Date</span>
                <input type="date" name="due_date" value="{{ old('due_date', optional($project->due_date ?? null)->format('Y-m-d')) }}">
                @error('due_date')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span>Description</span>
            <textarea name="description" rows="6">{{ old('description', $project->description ?? '') }}</textarea>
            @error('description')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
