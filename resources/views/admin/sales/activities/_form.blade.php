@php
    $activity = $activity ?? null;
    $selectedRelatedType = old('related_type', $activity->related_type ?? $prefillRelatedType ?? 'lead');
    $selectedRelatedId = old('related_id', $activity->related_id ?? $prefillRelatedId ?? '');
    $selectedType = old('type', $activity->type ?? 'call');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Related Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Related Type <strong>*</strong></span>
                <select name="related_type" id="related_type" required>
                    @foreach ($relatedTypeOptions as $relatedType)
                        <option value="{{ $relatedType }}" @selected($selectedRelatedType === $relatedType)>{{ ucfirst($relatedType) }}</option>
                    @endforeach
                </select>
                @error('related_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Related Data <strong>*</strong></span>
                <select name="related_id" id="related_id" required>
                    <option value="">Pilih related data</option>
                    @foreach ($relatedOptions as $relatedType => $options)
                        @foreach ($options as $option)
                            @php
                                $label = $relatedType === 'opportunity' ? $option->title : $option->name;
                            @endphp
                            <option value="{{ $option->id }}" data-related-type="{{ $relatedType }}" @selected($selectedRelatedType === $relatedType && (string) $selectedRelatedId === (string) $option->id)>
                                {{ ucfirst($relatedType) }}: {{ $label }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
                @error('related_id')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Activity Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Type <strong>*</strong></span>
                <select name="type" required>
                    @foreach ($typeOptions as $type)
                        <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
                @error('type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Subject <strong>*</strong></span>
                <input type="text" name="subject" value="{{ old('subject', $activity->subject ?? '') }}" maxlength="255" required>
                @error('subject')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span>Description</span>
            <textarea name="description" rows="6">{{ old('description', $activity->description ?? '') }}</textarea>
            @error('description')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2>Assignment</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Assigned To</span>
                <input type="text" name="assigned_to" value="{{ old('assigned_to', $activity->assigned_to ?? '') }}" maxlength="255">
                @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Outcome</span>
                <input type="text" name="outcome" value="{{ old('outcome', $activity->outcome ?? '') }}" maxlength="255">
                @error('outcome')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Activity At</span>
                <input type="datetime-local" name="activity_at" value="{{ old('activity_at', optional($activity->activity_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('activity_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const relatedType = document.getElementById('related_type');
        const relatedId = document.getElementById('related_id');

        if (!relatedType || !relatedId) {
            return;
        }

        function syncRelatedOptions() {
            const selectedType = relatedType.value;
            let hasVisibleSelected = false;

            Array.from(relatedId.options).forEach(function (option) {
                if (!option.dataset.relatedType) {
                    option.hidden = false;
                    return;
                }

                const isVisible = option.dataset.relatedType === selectedType;
                option.hidden = !isVisible;

                if (isVisible && option.selected) {
                    hasVisibleSelected = true;
                }
            });

            if (!hasVisibleSelected) {
                relatedId.value = '';
            }
        }

        relatedType.addEventListener('change', syncRelatedOptions);
        syncRelatedOptions();
    });
</script>
